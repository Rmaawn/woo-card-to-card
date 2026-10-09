<?php
/**
 * Bank Card Manager
 *
 * Handles Iranian bank card detection, formatting, and selection logic.
 *
 * @package Woo_Card_To_Card_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Card_Manager {

	/**
	 * Map of known Iranian Bank BINs (first 6 digits) to bank metadata
	 *
	 * @var array
	 */
	private static $banks_bin = array(
		'603799' => array( 'name' => 'بانک ملی ایران', 'theme' => 'melli', 'color' => '#1a3b8b' ),
		'610433' => array( 'name' => 'بانک ملت', 'theme' => 'mellat', 'color' => '#b3002d' ),
		'621986' => array( 'name' => 'بانک سامان', 'theme' => 'saman', 'color' => '#0c66a4' ),
		'502229' => array( 'name' => 'بانک پاسارگاد', 'theme' => 'pasargad', 'color' => '#d4af37' ),
		'627412' => array( 'name' => 'بانک اقتصاد نوین', 'theme' => 'en', 'color' => '#5e2750' ),
		'627353' => array( 'name' => 'بانک تجارت', 'theme' => 'tejarat', 'color' => '#183863' ),
		'589210' => array( 'name' => 'بانک سپه', 'theme' => 'sepah', 'color' => '#e67300' ),
		'622106' => array( 'name' => 'بانک پارسیان', 'theme' => 'parsian', 'color' => '#800020' ),
		'504706' => array( 'name' => 'بانک شهر', 'theme' => 'shahr', 'color' => '#c2185b' ),
		'639346' => array( 'name' => 'بانک سینا', 'theme' => 'sina', 'color' => '#1b5e20' ),
		'502908' => array( 'name' => 'بانک توسعه تعاون', 'theme' => 'tt', 'color' => '#00838f' ),
		'603770' => array( 'name' => 'بانک کشاورزی', 'theme' => 'keshavarzi', 'color' => '#2e7d32' ),
		'628023' => array( 'name' => 'بانک مسکن', 'theme' => 'maskan', 'color' => '#e65100' ),
		'639607' => array( 'name' => 'بانک سرمایه', 'theme' => 'sarmayeh', 'color' => '#0277bd' ),
		'505785' => array( 'name' => 'بانک ایران‌زمین', 'theme' => 'iranzamin', 'color' => '#6a1b9a' ),
		'636214' => array( 'name' => 'بانک آینده', 'theme' => 'ayandeh', 'color' => '#4527a0' ),
		'505416' => array( 'name' => 'بانک گردشگری', 'theme' => 'gardeshgari', 'color' => '#c62828' ),
		'639599' => array( 'name' => 'بانک قوامین', 'theme' => 'ghavamin', 'color' => '#37474f' ),
		'627381' => array( 'name' => 'بانک انصار', 'theme' => 'ansar', 'color' => '#1565c0' ),
		'505801' => array( 'name' => 'بانک کوثر', 'theme' => 'kosar', 'color' => '#2e7d32' ),
		'507677' => array( 'name' => 'بانک نور', 'theme' => 'noor', 'color' => '#00695c' ),
		'639217' => array( 'name' => 'بانک کشاورزی', 'theme' => 'keshavarzi', 'color' => '#2e7d32' ),
		'603769' => array( 'name' => 'بانک صادرات', 'theme' => 'saderat', 'color' => '#1a237e' ),
		'627760' => array( 'name' => 'پست بانک ایران', 'theme' => 'postbank', 'color' => '#2e7d32' ),
		'627961' => array( 'name' => 'بانک صنعت و معدن', 'theme' => 'sanatmadan', 'color' => '#424242' ),
		'628157' => array( 'name' => 'موسسه اعتباری توسعه', 'theme' => 'tosee', 'color' => '#00838f' ),
		'8619'   => array( 'name' => 'بلو بانک (سامان)', 'theme' => 'blu', 'color' => '#1064ea' ),
	);

	/**
	 * Format 16-digit card number with dash or space: 1234-5678-9012-3456
	 *
	 * @param string $card_number
	 * @param string $delimiter
	 * @return string
	 */
	public static function format_card_number( $card_number, $delimiter = ' - ' ) {
		$clean = preg_replace( '/\D/', '', $card_number );
		if ( strlen( $clean ) !== 16 ) {
			return $card_number;
		}
		return implode( $delimiter, str_split( $clean, 4 ) );
	}

	/**
	 * Format Shaba number: IR00 0000 0000 0000 0000 0000
	 *
	 * @param string $shaba
	 * @return string
	 */
	public static function format_shaba( $shaba ) {
		$clean = strtoupper( preg_replace( '/[^a-zA-Z0-9]/', '', $shaba ) );
		if ( strpos( $clean, 'IR' ) !== 0 ) {
			$clean = 'IR' . $clean;
		}
		return chunk_split( $clean, 4, ' ' );
	}

	/**
	 * Detect bank info from card number
	 *
	 * @param string $card_number
	 * @return array
	 */
	public static function detect_bank( $card_number ) {
		$clean = preg_replace( '/\D/', '', $card_number );
		$bin6 = substr( $clean, 0, 6 );
		$bin4 = substr( $clean, 0, 4 );

		if ( isset( self::$banks_bin[ $bin6 ] ) ) {
			return self::$banks_bin[ $bin6 ];
		}

		if ( isset( self::$banks_bin[ $bin4 ] ) ) {
			return self::$banks_bin[ $bin4 ];
		}

		return array(
			'name'  => 'بانک عضو شبکه شتاب',
			'theme' => 'default',
			'color' => '#2c3e50'
		);
	}

	/**
	 * Parse cards configuration from plugin settings
	 *
	 * Expects an array or a serialized/JSON string of cards.
	 * Also supports simple text lines if configured that way.
	 *
	 * @param mixed $cards_data
	 * @return array
	 */
	public static function parse_cards( $cards_data ) {
		if ( is_array( $cards_data ) ) {
			$cards = $cards_data;
		} elseif ( is_string( $cards_data ) && ! empty( $cards_data ) ) {
			$decoded = json_decode( $cards_data, true );
			if ( is_array( $decoded ) ) {
				$cards = $decoded;
			} else {
				// Fallback: parse line by line
				// Format: CardNumber | HolderName | BankName | Shaba
				$lines = explode( "\n", str_replace( "\r", "", $cards_data ) );
				$cards = array();
				foreach ( $lines as $line ) {
					$parts = array_map( 'trim', explode( '|', $line ) );
					if ( ! empty( $parts[0] ) ) {
						$cards[] = array(
							'card_number' => $parts[0],
							'card_holder' => isset( $parts[1] ) ? $parts[1] : '',
							'bank_name'   => isset( $parts[2] ) ? $parts[2] : '',
							'shaba'       => isset( $parts[3] ) ? $parts[3] : '',
							'active'      => 1
						);
					}
				}
			}
		} else {
			$cards = array();
		}

		// Enrich cards with detected info and formatting
		$result = array();
		foreach ( $cards as $c ) {
			$clean_card = preg_replace( '/\D/', '', isset( $c['card_number'] ) ? $c['card_number'] : '' );
			if ( strlen( $clean_card ) < 16 ) {
				continue;
			}

			$detected = self::detect_bank( $clean_card );
			$bank_name = ! empty( $c['bank_name'] ) ? $c['bank_name'] : $detected['name'];

			$result[] = array(
				'raw_card_number' => $clean_card,
				'card_number'     => self::format_card_number( $clean_card ),
				'card_holder'     => isset( $c['card_holder'] ) ? sanitize_text_field( $c['card_holder'] ) : '',
				'bank_name'       => sanitize_text_field( $bank_name ),
				'shaba'           => isset( $c['shaba'] ) ? sanitize_text_field( $c['shaba'] ) : '',
				'theme'           => $detected['theme'],
				'color'           => $detected['color'],
				'active'          => ! isset( $c['active'] ) || ! empty( $c['active'] )
			);
		}

		return $result;
	}

	/**
	 * Get the active card to display for an order
	 *
	 * @param array $cards
	 * @param int|null $order_id
	 * @return array|null
	 */
	public static function get_active_card_for_order( $cards, $order_id = null ) {
		$active_cards = array_filter( $cards, function ( $c ) {
			return ! empty( $c['active'] );
		} );

		if ( empty( $active_cards ) ) {
			return null;
		}

		$active_cards = array_values( $active_cards );

		// If order_id is provided, select card consistently based on order ID (or round-robin)
		if ( $order_id ) {
			$index = $order_id % count( $active_cards );
			return $active_cards[ $index ];
		}

		// Default to first active card
		return $active_cards[0];
	}
}
