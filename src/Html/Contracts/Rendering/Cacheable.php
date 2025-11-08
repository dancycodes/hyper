<?php

namespace Dancycodes\Hyper\Html\Contracts\Rendering;

interface Cacheable
{
    /**
     * Generate a unique cache key for this element
     */
    public function cacheKey(): string;

    /**
     * Render and cache the element output
     *
     * @param int $ttl Time to live in seconds
     */
    public function cached(int $ttl = 3600): string;
}
