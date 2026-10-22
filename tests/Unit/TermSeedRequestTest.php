<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\SiteStructure\PagedStructureRequest;
use PHPUnit\Framework\TestCase;

final class TermSeedRequestTest extends TestCase {
	public function test_it_rejects_non_exact_pagination_keys_and_bounds(): void {
		$this->expectException( InvalidArgumentException::class );

		PagedStructureRequest::from_input( array( 'limit' => 21, 'page' => 1, 'taxonomy' => 'category' ) );
	}
}
