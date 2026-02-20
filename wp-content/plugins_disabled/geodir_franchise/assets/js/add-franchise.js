jQuery(function($) {
    if ($('form [name="franchise"]').length) {
        GeoDir_Add_Franchise.init($('form [name="franchise"]').closest('form'));
    }
});
var GeoDir_Add_Franchise = {
    init: function($form) {
        var $self = this;
        this.$form = $form;

        jQuery('[name="franchise"]', $form).on('click', function(e) {
            $self.onChangeFranchise(jQuery(this));
        });
        jQuery('[name="franchise"]:checked', $form).trigger('click');
    },
    onChangeFranchise: function($el) {
        var $self = this, $row = jQuery('#franchise_fields', $self.form);

        if (parseInt($el.val()) == 1) {
            $row.closest('#franchise_fields_row').slideDown(200);
            $row.closest('.form-group,[data-argument="franchise_fields"]').slideDown(200);
        } else {
            $row.closest('#franchise_fields_row').slideUp(200);
            $row.closest('.form-group,[data-argument="franchise_fields"]').slideUp(200);
        }
    }
}