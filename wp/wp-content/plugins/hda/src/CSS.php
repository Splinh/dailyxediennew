<?php
/**
 * Creates minified CSS via PHP.
 *
 * @author  Carlos Rios
 *
 * Modified by Tom Usborne for GeneratePress
 * Modified by HD for PHP 8.3
 */

namespace HDAddons;

defined( 'ABSPATH' ) || exit;

final class CSS {
	/* ---------- CONFIG ------------------------------------------- */

	private string $selector         = '';
	private string $css              = '';
	private string $output           = '';
	private ?string $mediaQuery      = null;
	private string $mediaQueryOutput = '';

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * Sets a selector to the object and changes the current selector to a new one
	 *
	 * @param string $selector - the CSS identifier of the HTML that you wish to target.
	 *
	 * @return self
	 */
	public function setSelector( string $selector = '' ): self {
		// Render the CSS in the output string everytime the selector changes.
		if ( $this->selector !== '' ) {
			$this->addSelectorRulesToOutput();
		}

		$this->selector = $selector;

		return $this;
	}

	// -----------------------------------------

	/**
	 * Adds a CSS property with value to the CSS output
	 *
	 * @param string $property The CSS property.
	 * @param mixed $value The value to be placed with the property.
	 * @param mixed $ogDefault Check to see if the value matches the default.
	 * @param mixed $unit The unit for the value (px).
	 *
	 * @return self
	 */
	public function addProperty( string $property, string|int|float $value, string|int|float|false $ogDefault = false, string|false $unit = false ): self {
		// Setting font-size to 0 is rarely ever a good thing.
		if ( $property === 'font-size' && $value === 0 ) {
			return $this;
		}

		// Add our unit to our value if it exists.
		if ( $unit && is_numeric( $value ) ) {
			$value .= $unit;
			if ( $ogDefault ) {
				$ogDefault .= $unit;
			}
		}

		// If we don't have a value or our value is the same as our og default, bail.
		if ( ( empty( $value ) && ! is_numeric( $value ) ) || $ogDefault === $value ) {
			return $this;
		}

		$this->css .= $property . ':' . $value . ';';

		return $this;
	}

	// -----------------------------------------

	/**
	 * Sets a media query in the class
	 *
	 * @param ?string $value The media query.
	 *
	 * @return self
	 */
	public function startMediaQuery( ?string $value = null ): self {
		// Add the current rules to the output.
		$this->addSelectorRulesToOutput();

		// Add any previous media queries to the output.
		if ( $this->mediaQuery ) {
			$this->addMediaQueryRulesToOutput();
		}

		// Set the new media query.
		$this->mediaQuery = $value;

		return $this;
	}

	// -----------------------------------------

	/**
	 * Stops using a media query.
	 *
	 * @return self
	 */
	public function stopMediaQuery(): self {
		return $this->startMediaQuery();
	}

	/* ---------- PRIVATE ------------------------------------------ */

	/**
	 * Adds the current media query's rules to the class' output variable
	 *
	 * @return void
	 */
	private function addMediaQueryRulesToOutput(): void {
		if ( $this->mediaQueryOutput ) {
			$this->output .= sprintf( '@media %1$s{%2$s}', $this->mediaQuery, $this->mediaQueryOutput );

			// Reset the media query output string.
			$this->mediaQueryOutput = '';
		}
	}

	// -----------------------------------------

	/**
	 * Adds the current selector rules to the output variable
	 *
	 * @return void
	 */
	private function addSelectorRulesToOutput(): void {
		if ( ! $this->css ) {
			return;
		}

		$selectorOutput = sprintf( '%1$s{%2$s}', $this->selector, $this->css );

		// Add our CSS to the output.
		if ( $this->mediaQuery ) {
			$this->mediaQueryOutput .= $selectorOutput;
		} else {
			$this->output .= $selectorOutput;
		}

		// Reset the css.
		$this->css = '';
	}

	// -----------------------------------------

	/**
	 * Returns the minified CSS in the $output variable
	 *
	 * @return string
	 */
	public function cssOutput(): string {
		// Add current selector's rules to output.
		$this->addSelectorRulesToOutput();

		// Flush any remaining media query rules.
		$this->addMediaQueryRulesToOutput();

		// Output minified CSS.
		return $this->output;
	}
}
