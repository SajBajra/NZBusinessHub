(function ($, iCal) {
    "use strict";

    $(function () {
        /**
         * Performs AJAX request.
         * @param {string} action - Action to perform.
         * @param {function} callback - Callback function.
         * @param {Object} data - Data to send (optional).
         * @param {Object} atts - Additional parameters for $.ajax (optional).
         * @returns {jqXHR} - jQuery XMLHttpRequest object.
         */
        function ajax(action, callback, data, atts) {
            atts = (typeof atts !== 'undefined') ? atts : {};
            data = (typeof data !== 'undefined') ? data : {};
 
            data['action'] = action;
            data['geodir_booking_nonce'] = iCal.nonces.hasOwnProperty(action) ? iCal.nonces[action] : '';

            atts = $.extend(atts, {
                url: iCal.ajaxUrl,
                dataType: 'json',
                data: data,
                success: function (response, textStatus, jqXHR) {
                    var success = true === response.success;
                    var responseData = response.data || {};

                    callback(success, responseData);
                },
            });

            return $.ajax(atts);
        }

        /**
         * Controls buttons.
         */
        iCal.ControlButton = {
            inSuspended: false,
            wasDisabled: false,
            defaultText: '',
            actionText: '',
            ajaxAction: '',
            importer: null,
            init: function (el, args) {
                this.element = el;
                this.defaultText = args.defaultText;
                this.actionText = args.actionText;
                this.ajaxAction = args.ajaxAction;
                this.importer = args.importer;

                $(el).on('click', this.click.bind(this));

                return this;
            },
            click: function (el) {
                if (this.inSuspended) {
                    return false;
                } else {
                    this.doAction();
                }
            },
            doAction: function () { },
            activate: function () {
                this.inSuspended = true;
                this.element.prop('disabled', true);
                this.element.text(this.actionText);
            },
            enable: function () {
                this.inSuspended = false;
                this.element.prop('disabled', false);
                this.element.text(this.defaultText);
            },
            disable: function () {
                this.inSuspended = false;
                this.element.prop('disabled', true);
                this.element.text(this.defaultText);
            },
            suspend: function () {
                this.inSuspended = true;
                this.wasDisabled = !!this.element.prop('disabled');
                this.element.prop('disabled', true);
            },
            restore: function () {
                this.inSuspended = false;
                this.element.prop('disabled', this.wasDisabled);
            }
        };

        /**
         * Abort Process Button Control
         */
        iCal.AbortButton = $.extend({}, iCal.ControlButton, {
            doAction: function () {
                this.activate();
                this.importer.stop();
        
                var self = this;
        
                ajax(self.ajaxAction, function (success, data) {
                    self.importer.start();
                    if (!success) {
                        self.enable();
                    }
                }, {}, { method: 'POST' });
            }
        });

        /**
         * Clear All Button Control
         */
        iCal.ClearAllButton = $.extend({}, iCal.ControlButton, {
            doAction: function () {
                this.activate();
                this.importer.stop();
                this.importer.trigger('geodir_booking:clear_all:before');
        
                var self = this;
        
                ajax(this.ajaxAction, function (success, data) {
                    if (success) {
                        self.importer.trigger('geodir_booking:clear_all');
                        self.disable();
                    } else {
                        self.importer.trigger('geodir_booking:clear_all:failed');
                        self.enable();
                    }
                    self.importer.start();
                }, {}, { method: 'POST' });
            }
        });

        iCal.DetailsTableRoom = $.extend({}, {
            key: '', // "564218900_38"
            status: '', // "wait"|"in-progress"|"done"
            statusEl: null,
            totalEl: null,
            succeedEl: null,
            failedEl: null,
            skippedEl: null,
            removedEl: null,
            oldStatusClass: '',
            emptyValuePlaceholder: '&#8212;',
        
            init: function (el, args) {
                this.element = el;
                this.key = el.attr('data-item-key');
                this.status = el.attr('data-sync-status');
        
                this.statusEl = el.find('.column-status > span');
        
                this.totalEl = el.find('.column-total');
                this.succeedEl = el.find('.column-succeed');
                this.failedEl = el.find('.column-failed');
                this.skippedEl = el.find('.column-skipped');
                this.removedEl = el.find('.column-removed');
        
                this.oldStatusClass = this.statusEl.attr('class');

                return this;
            },
            changeContent: function (data) {
                // Update status
                this.status = data.status.code;
                this.element.attr('data-sync-status', this.status);
        
                // Update status class
                this.statusEl.removeClass(this.oldStatusClass);
                this.statusEl.addClass(data.status.class);
        
                this.oldStatusClass = data.status.class;
        
                // Update status text
                this.statusEl.text(data.status.text);
        
                // Update numbers
                data.stats.total != 0 ? this.totalEl.text(data.stats.total) : this.totalEl.html(this.emptyValuePlaceholder);
                data.stats.succeed != 0 ? this.succeedEl.text(data.stats.succeed) : this.succeedEl.html(this.emptyValuePlaceholder);
                data.stats.failed != 0 ? this.failedEl.text(data.stats.failed) : this.failedEl.html(this.emptyValuePlaceholder);
                data.stats.skipped != 0 ? this.skippedEl.text(data.stats.skipped) : this.skippedEl.html(this.emptyValuePlaceholder);
                data.stats.removed != 0 ? this.removedEl.text(data.stats.removed) : this.removedEl.html(this.emptyValuePlaceholder);
        
                // Add "have errors" class
                if (data.stats.failed > 0) {
                    this.element.addClass('geodir-booking-have-errors');
                }
            },
            clear: function () {
                this.element.remove();
            },
            getKey: function () {
                return this.key;
            },
            getStatus: function () {
                return this.status;
            }
        });
        
        iCal.DetailsTable = $.extend({}, {
            itemsSingularText: '%d item',
            itemsPluralText: '%d items',
            ajaxAction: '',
            importer: null, // iCal.SyncImporter in sync-importer.js
            rooms: {}, 
            roomsCount: 0, // Count only on current page
            roomsCountEl: null,
        
            init: function (el, args) {
                this.element = el;
                this.itemsSingularText = args.itemsSingularText;
                this.itemsPluralText = args.itemsPluralText;
        
                this.ajaxAction = args.ajaxAction;
        
                this.importer = args.importer;
        
                this.initRooms();
                this.initRemoveButtons();
        
                this.roomsCountEl = el.parent().find('.displaying-num');
                return this;
            },
            initRooms: function () {
                var roomElements = this.element.find('tbody > tr:not(.geodir-booking-logs-wrapper):not(.no-items)');
                var rooms = {};
                var roomsCount = 0;
        
                // Fetch rooms
                roomElements.each(function (index, el) {
                    var room = Object.create(iCal.DetailsTableRoom).init($(el));
                    
                    var key = room.getKey();
                    rooms[key] = room;
        
                    roomsCount++;
                });

                this.rooms = rooms;
                this.roomsCount = roomsCount;
            },
            initRemoveButtons: function () {
                var self = this;
        
                this.element.find('.geodir-booking-remove-item')
                    .addClass('geodir-booking-inited')
                    .on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
        
                        // Get room key
                        var roomEl = $(this).parents('tr');
                        var key = roomEl.attr('data-item-key');
        
                        // Remove by key
                        if (key && self.rooms[key]) {
                            $(this).prop('disabled', true);
                            self.removeRoomByKey(key);
                        }
                    });
            },
            removeRoomByKey: function (roomKey) {
                this.importer.stop();
        
                var self = this;
        
                ajax(this.ajaxAction, function (success) {
                    // Remove the room or enable the button
                    if (success) {
                        self.removeRoom(self.rooms[roomKey]);
                    } else {
                        self.element.find('.geodir-booking-remove-item:disabled').prop('disabled', false);
                    }
                    self.importer.start();
                }, {
                    geodir_booking_room_key: roomKey
                }, {
                    method: 'POST'
                });
        
            },
            removeRoom: function (room) {
                room.clear();
        
                delete this.rooms[room.getKey()];
                this.roomsCount--;
        
                this.decreaseCountInView();
        
                if (this.isEmpty()) {
                    // Reload the page (to show items from other pages)
                    location.reload(true); // true - load from server, not from the cache
                }
            },
            clear: function () {
                var self = this;
                $.each(this.rooms, function (index, room) {
                    self.removeRoom(room);
                });
                this.roomsCount = 0;
            },
            isEmpty: function () {
                return this.roomsCount == 0;
            },
            changeContent: function (rooms) {
                var self = this;
        
                $.each(rooms, function (key, roomData) {
                    if (self.rooms[key] != undefined) {
                        self.rooms[key].changeContent(roomData);
                    }
                });
            },
            decreaseCountInView: function () {
                var totalText = this.roomsCountEl.text(); // "24 items"
        
                var match = totalText.match(/\d+/); // [0: "24", index: 0, input: "24 items"] or null (if failed)
        
                if (match != null) {
                    var totalCount = parseInt(match[0]);
                    totalCount--;
        
                    var textTemplate = (totalCount == 1) ? this.itemsSingularText : this.itemsPluralText;
                    var text = textTemplate.replace('%d', totalCount);
        
                    this.roomsCountEl.text(text);
                }
            },
            getKeysOfUndone: function () {
                var keys = [];

                // TODO Don't search for undone rooms every time, make a list with such rooms
                $.each(this.rooms, function (key, room) {
                    // TODO Use value "done" from constant Queue::STATUS_DONE
                    if (room.getStatus() != 'done') {
                        keys.push(key);
                    }
                });
        
                return keys;
            }
        });

        iCal.ImportStats = $.extend({}, {
            totalEl: null,
            succeedEl: null,
            skippedEl: null,
            failedEl: null,
            removedEl: null,
            init: function (el, args) {
                this.element = el;
                this.totalEl = this.element.find('.geodir-booking-total');
                this.succeedEl = this.element.find('.geodir-booking-succeed');
                this.skippedEl = this.element.find('.geodir-booking-skipped');
                this.failedEl = this.element.find('.geodir-booking-failed');
                this.removedEl = this.element.find('.geodir-booking-removed');
                return this;

            },
            updateStats: function (data) {
                // Update process info
                this.totalEl.text(data.total);
                this.succeedEl.text(data.succeed);
                this.skippedEl.text(data.skipped);
                this.failedEl.text(data.failed);
                this.removedEl.text(data.removed);
            }
        });
        
        iCal.Importer = $.extend({}, {
            tickInterval: 2000,
            shortTickInterval: 500, // Show response faster for rooms with no sync URLs
            retriesCount: 1,
            retriesLeft: 0,
            inProgress: false,
            updateTimeout: null,
            preventUpdates: false,
            init: function (el, args) {
                this.element = el;
                this.inProgress = args.inProgress;
                this.resetRetries();
                if (this.inProgress) {
                    this.start();
                }
                return this;
            },
            start: function () {
                this.preventUpdates = false;
                this.updateTimeout = setTimeout(this.tick.bind(this), this.shortTickInterval);
            },
            requestUpdate: function () {
                this.preventUpdates = false;
                this.updateTimeout = setTimeout(this.tick.bind(this), this.tickInterval);
            },
            stop: function () {
                clearTimeout(this.updateTimeout);
                this.preventUpdates = true;
            },
            resetRetries: function () {
                this.retriesLeft = this.retriesCount;
            },
            markInProgress: function () {
                this.inProgress = true;
            },
            markStopped: function () {
                this.inProgress = false;
            },
            trigger: function (event) {
                this.element.trigger(event);
            }
        });

        iCal.LogsHandler = $.extend({}, {
            shown: 0,

            init: function (el, args) {
                this.element = el;
                return this;
            },

            insertLogs: function (logs) {
                this.element.append(logs);
            },
            /**
             *
             * @param {int} count
             * @returns {undefined}
             */
            setShown: function (count) {
                this.shown = count;
            }
        });
        
        iCal.ProgressBar = $.extend({}, {
            barEl: null,
            textEl: null,
            init: function (el, args) {
                this.element = el;
                this.barEl = this.element.find('.geodir-booking-progress__bar');
                this.textEl = this.element.find('.geodir-booking-progress__text');
                return this;
            },
            updateProgress: function (newProgress) {
                this.barEl.css('width', newProgress + '%');
                this.textEl.text(newProgress + '%');
            }
        });

        /**
         * Controls importer.
         */
        iCal.Importer = {
            tickInterval: 2000,
            shortTickInterval: 500,
            retriesCount: 1,
            retriesLeft: 0,
            inProgress: false,
            updateTimeout: null,
            preventUpdates: false,
            init: function (el, args) {
                this.element = el;
                this.inProgress = args.inProgress;
                this.resetRetries();
                if (this.inProgress) {
                    this.start();
                }

                return this;
            },
            start: function () {
                this.preventUpdates = false;
                this.updateTimeout = setTimeout(this.tick.bind(this), this.shortTickInterval);
            },
            requestUpdate: function () {
                this.preventUpdates = false;
                this.updateTimeout = setTimeout(this.tick.bind(this), this.tickInterval);
            },
            stop: function () {
                clearTimeout(this.updateTimeout);
                this.preventUpdates = true;
            },
            resetRetries: function () {
                this.retriesLeft = this.retriesCount;
            },
            markInProgress: function () {
                this.inProgress = true;
            },
            markStopped: function () {
                this.inProgress = false;
            },
            trigger: function (event) {
                if (this.hasOwnProperty(event)) {
                    this[event]();
                }
                this.element.trigger(event);
            }
        };

        /**
         * Sync importer.
         */
        iCal.SyncImporter = $.extend({}, iCal.Importer, {
            abortButton: null,
            clearAllButton: null,
            detailsTable: null,

            init: function (el, args) {
                this._super = iCal.Importer.init;
                this._super(el, args);

                this.detailsTable = iCal.DetailsTable.init(this.element.find('.geodir-booking-ical-sync-table'), {
                    itemsSingularText: iCal.i18n.items_singular,
                    itemsPluralText: iCal.i18n.items_plural,
                    ajaxAction: iCal.actions.sync.remove_item,
                    importer: this
                });

                this.abortButton = iCal.AbortButton.init(this.element.find('.geodir-booking-abort-process'), {
                    defaultText: iCal.i18n.abort,
                    actionText: iCal.i18n.aborting,
                    ajaxAction: iCal.actions.sync.abort,
                    importer: this
                });

                this.clearAllButton = iCal.ClearAllButton.init(this.element.find('.geodir-booking-clear-all'), {
                    defaultText: iCal.i18n.clear,
                    actionText: iCal.i18n.clearing,
                    ajaxAction: iCal.actions.sync.clear_all,
                    importer: this
                });
            },
            tick: function () {
                var self = this;

                var undoneListingKeys = this.detailsTable.getKeysOfUndone();

                ajax(iCal.actions.sync.progress, function (success, data) {
                    if (self.preventUpdates) {
                        return;
                    }

                    if (!success) {
                        if (self.retriesLeft > 0) {
                            self.retriesLeft--;
                            self.requestUpdate();
                        } else {
                            self.abortButton.disable();
                        }
                        return;
                    } else {
                        self.resetRetries();
                    }

                    data.inProgress ? self.markInProgress() : self.markStopped();

                    self.detailsTable.changeContent(data.items);

                    if (self.inProgress) {
                        self.requestUpdate();
                    } else {
                        self.abortButton.disable();
                    }

                }, {
                    focus: undoneListingKeys
                }, {
                    method: 'POST'
                });
            },
            "geodir_booking:clear_all": function () {
                this.abortButton.disable();
                this.clearAllButton.disable();
                this.detailsTable.clear();
            },
            "geodir_booking:clear_all:before": function () {
                this.abortButton.suspend();
            },
            "geodir_booking:clear_all:failed": function () {
                this.abortButton.restore();
            }
        });

        iCal.UploadImporter = $.extend({}, iCal.Importer, {
            progressBar: null,
            logsHandler: null,
            importStats: null,
            init: function (el, args) {
                this._super = iCal.Importer.init;
                this._super(el, args);
                
                this.progressBar = iCal.ProgressBar.init(this.element.find('.geodir-booking-progress'));
                this.logsHandler = iCal.LogsHandler.init(this.element.find('.geodir-booking-logs'));
                this.importStats = iCal.ImportStats.init(this.element.find('.geodir-booking-import-stats'));
                this.abortButton = iCal.AbortButton.init(this.element.find('.geodir-booking-abort-process'), {
                    defaultText: iCal.i18n.abort,
                    actionText: iCal.i18n.aborting,
                    ajaxAction: iCal.actions.upload.abort,
                    importer: this
                });
            },
            tick: function () {
                var self = this;
                ajax(iCal.actions.upload.progress, function (success, data) {

                    // Request failed?
                    if (!success) {
                        if (self.retriesLeft > 0) {
                            self.retriesLeft--;
                            self.requestUpdate();
                        } else {
                            self.abortButton.disable();
                        }
                        return;
                    } else {
                        self.resetRetries();
                    }

                    data.isFinished ? self.markStopped() : self.markInProgress();

                    self.importStats.updateStats(data);
                    self.progressBar.updateProgress(data.progress);

                    self.logsHandler.setShown(data.logsShown);
                    self.logsHandler.insertLogs(data.logs);

                    // Insert notice when finished
                    $(data.notice).insertAfter('.wp-heading-inline');

                    if (self.inProgress) {
                        self.requestUpdate();
                    } else {
                        self.abortButton.disable();
                    }

                }, { "logsShown": self.logsHandler.shown });
            }
        });

        const syncDetailsWrapper = $('.geodir-booking-sync-details-wrapper');
        if (syncDetailsWrapper.length) {
            iCal.SyncImporter.init(syncDetailsWrapper, {
                inProgress: iCal.inProgress
            });
        }

        const uploadImportWrapper = $('.geodir-booking-upload-import-details-wrapper');
        if (uploadImportWrapper.length) {
            iCal.UploadImporter.init(uploadImportWrapper, {
                inProgress: iCal.inProgress
            });
        }
    });
})(jQuery, Geodir_Booking_iCal);
