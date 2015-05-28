<?php

class Kint_Decorators_Log
{
	public static function decorate( kintVariableData $kintVar, $level = 0 )
	{
		$output = '';
		if ( $level === 0 ) {
			$name          = $kintVar->name ? $kintVar->name : 'literal';
			$kintVar->name = null;

			$output = date('H:i:s') . ', ' . kintParser::escape( $name ) . ' = ';
		}

		$space = str_repeat( $s = '    ', $level );
		$output .= $space . self::_drawHeader( $kintVar );


		if ( $kintVar->extendedValue !== null ) {
			$output .= ' ' . ( $kintVar->type === 'array' ? '[' : '(' ) . PHP_EOL;


			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v, $level + 1 );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= $space . $s . $kintVar->extendedValue . PHP_EOL; # depth too great or similar
			} else {
				$output .= self::decorate( $kintVar->extendedValue, $level + 1 ); //it's kintVariableData
			}
			$output .= $space . ( $kintVar->type === 'array' ? ']' : ')' ) . PHP_EOL;
		} else {
			$output .= PHP_EOL;
		}

		return $output;
	}

	public static function decorateTrace( $traceData )
	{
		$output   = 'TRACE';
		$lastStep = count( $traceData );
		foreach ( $traceData as $stepNo => $step ) {
			$title = str_pad( ++$stepNo . ': ', 4, ' ' );

			$title .= ( isset( $step['file'] ) ? self::_buildCalleeString( $step ) : 'PHP internal call' );

			if ( !empty( $step['function'] ) ) {
				$title .= '    ' . $step['function'];
				if ( isset( $step['args'] ) ) {
					$title .= '(';
					if ( empty( $step['args'] ) ) {
						$title .= ')';
					} else {
					}
					$title .= PHP_EOL;
				}
			}

			$output .= $title;

			if ( !empty( $step['args'] ) ) {
				$appendDollar = $step['function'] === '{closure}' ? '' : '$';

				$i = 0;
				foreach ( $step['args'] as $name => $argument ) {
					$argument           = kintParser::factory(
						$argument,
						$name ? $appendDollar . $name : '#' . ++$i
					);
					$argument->operator = $name ? ' =' : ':';
					$maxLevels          = Kint::$maxLevels;
					if ( $maxLevels ) {
						Kint::$maxLevels = $maxLevels + 2;
					}
					$output .= self::decorate( $argument, 2 );
					if ( $maxLevels ) {
						Kint::$maxLevels = $maxLevels;
					}
				}
				$output .= '    )' . PHP_EOL;
			}

			if ( !empty( $step['object'] ) ) {
				$output .= '    ' . PHP_EOL . ' Callee object ' . PHP_EOL;

				$maxLevels = Kint::$maxLevels;
				if ( $maxLevels ) {
					# in cli the terminal window is filled too quickly to display huge objects
					Kint::$maxLevels = $maxLevels + 1;
				}
				$output .= self::decorate( kintParser::factory( $step['object'] ), 1 );
				if ( $maxLevels ) {
					Kint::$maxLevels = $maxLevels;
				}
			}

			if ( $stepNo !== $lastStep ) {
				$output .= PHP_EOL;
			}
		}

		return $output;
	}

	public static function wrapStart()
	{
		return '';
	}

	public static function wrapEnd( $callee, $miniTrace, $prevCaller )
	{
		$lastChar = PHP_EOL;


		if ( !Kint::$displayCalledFrom ) return $lastChar;

		return 'Called from ' . self::_buildCalleeString( $callee ) . $lastChar;
	}


	private static function _drawHeader( kintVariableData $kintVar )
	{
		$output = '';

		if ( $kintVar->access ) {
			$output .= ' ' . $kintVar->access;
		}

		if ( $kintVar->name !== null && $kintVar->name !== '' ) {
			$output .= ' ' . kintParser::escape( $kintVar->name );
		}

		if ( $kintVar->operator ) {
			$output .= $kintVar->operator;
		}

		$output .= ' ' . $kintVar->type;

		if ( $kintVar->size !== null ) {
			$output .= ' (' . $kintVar->size . ')';
		}


		if ( $kintVar->value !== null && $kintVar->value !== '' ) {
			$output .= ' ' . $kintVar->value;
		}

		return ltrim( $output );
	}

	private static function _buildCalleeString( $callee )
	{
		$calleeInfo = Kint::shortenPath( $callee['file'] ) . ':' . $callee['line'];

		return $calleeInfo;
	}

	public static function init()
	{
		return '';
	}
}