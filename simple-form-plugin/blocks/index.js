( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'simple-form/block', {
        title: 'Simple Form',
        icon: 'feedback',
        category: 'widgets',
        edit: function() {
            return el( 'p', {}, 'Formularvorschau' );
        },
        save: function() {
            return null; // Rendering via PHP
        }
    } );
} )( window.wp.blocks, window.wp.element );
