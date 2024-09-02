<?php

namespace RemoteDataBlocks\Config;

trait DynamicDatasource {
	private string $slug;
	private string $uuid;

	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	public function get_slug(): string {
		return $this->slug;
	}

	public function set_uuid( string $uuid ): void {
		$this->uuid = $uuid;
	}
	
	public function get_uuid(): string {
		return $this->uuid;
	}
}
