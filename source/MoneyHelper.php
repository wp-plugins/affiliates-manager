<?php

class WPAM_MoneyHelper {

	public static function getDollarSign() {
		$info = localeconv();
		if( ! empty( $info['currency_symbol'] ) ) {
			return $info['currency_symbol'];
		} else {
			return '$';
		}
	}

	public static function getCurrencyCode() {
		$info = localeconv();
		if( ! empty( $info['int_curr_symbol'] ) ) {
			return trim( $info['int_curr_symbol'] );
		} else {
			return 'USD';
		}
	}
}