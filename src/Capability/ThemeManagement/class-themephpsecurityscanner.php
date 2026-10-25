<?php
/**
 * Lightweight, fail-closed static scanner for PHP inside local theme archives.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

/**
 * Identifies a small closed set of high-risk PHP execution and obfuscation forms.
 *
 * This is deliberately a gate, not a malware verdict. Callers retain only the
 * fixed category returned here and must not disclose the matching source.
 */
final class ThemePhpSecurityScanner {
	/**
	 * Returns the fixed rejection category for suspicious PHP, or null when this
	 * narrow static policy finds no blocked form.
	 *
	 * @param string $source PHP source from exactly one archive member.
	 * @return 'backtick_execution'|'dynamic_include'|'execution'|'obfuscation'|'preg_replace_e'|null Fixed redacted reason.
	 */
	public static function rejection_reason( string $source ): ?string {
		$patterns = array(
			'execution'         => '/\\b(?:eval|assert|exec|shell_exec|system|passthru|proc_open|popen)\\s*\\(/i',
			'preg_replace_e'    => '/\\bpreg_replace\\s*\\(\\s*([\'"])(?:\\\\.|(?!\\1).)*\\/e[a-z]*\\1/is',
			'dynamic_include'   => '/\\b(?:include|include_once|require|require_once)\\s*(?:\\(\\s*)?\\$/i',
			'obfuscation'       => '/\\b(?:base64_decode|gzinflate|gzuncompress|str_rot13|convert_uudecode)\\s*\\(/i',
			'backtick_execution' => '/(?<![\\\\])`(?:\\\\.|[^`])+`/s',
		);

		foreach ( $patterns as $reason => $pattern ) {
			if ( 1 === preg_match( $pattern, $source ) ) {
				return $reason;
			}
		}

		return null;
	}
}
