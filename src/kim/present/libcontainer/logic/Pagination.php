<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\libcontainer\logic;

use InvalidArgumentException;
use function intdiv;
use function max;
use function min;

/** Page arithmetic for {@see \kim\present\libcontainer\PagedMenu}. Pure, no server needed. */
final class Pagination{
    private function __construct(){
    }

    /** Number of pages for $total entries. An empty list still has one (empty) page. */
    public static function pageCount(int $total, int $perPage) : int{
        self::assertPerPage($perPage);
        return max(1, intdiv(max(0, $total) + $perPage - 1, $perPage));
    }

    /** The nearest valid page index. */
    public static function clamp(int $page, int $total, int $perPage) : int{
        return max(0, min($page, self::pageCount($total, $perPage) - 1));
    }

    /**
     * Entry indexes shown on a page, as [first, count]. The page is clamped.
     *
     * @return array{int, int}
     */
    public static function window(int $page, int $total, int $perPage) : array{
        $page = self::clamp($page, $total, $perPage);
        $first = $page * $perPage;
        return [$first, max(0, min($perPage, $total - $first))];
    }

    public static function hasPrevious(int $page) : bool{
        return $page > 0;
    }

    public static function hasNext(int $page, int $total, int $perPage) : bool{
        return $page < self::pageCount($total, $perPage) - 1;
    }

    private static function assertPerPage(int $perPage) : void{
        if($perPage < 1){
            throw new InvalidArgumentException("Entries per page must be at least 1, got $perPage");
        }
    }
}
