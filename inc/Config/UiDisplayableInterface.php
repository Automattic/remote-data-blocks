<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config;

interface UiDisplayableInterface {
	/**
	 * Converts the current object to a displayable format.
	 *
	 * This method should return an array that can be used to display the object in a UI.
	 *
	 */
	public function to_ui_display(): array;
}
