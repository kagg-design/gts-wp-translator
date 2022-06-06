jQuery( document ).ready( function( $ ) {
	

	/**
	 * Get cookie by name.
	 *
	 * @param name
	 * @returns {string|null}
	 */
	function getCookie( name ) {
		function escape( s ) {
			return s.replace( /([.*+?\^$(){}|\[\]\/\\])/g, '\\$1' );
		}

		let match = document.cookie.match( RegExp( '(?:^|;\\s*)' + escape( name ) + '=([^;]*)' ) );
		return match ? match[ 1 ] : null;
	}
} );