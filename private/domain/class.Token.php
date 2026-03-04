<?php

final class Token extends StaysailEntity
{
    public $Member_id;
    public $balance;

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public static function getPackages(): array
    {
        return [
            1 => ['tokens' => 1000, 'price' => 10.00],
            2 => ['tokens' => 500,  'price' => 6.00],
            3 => ['tokens' => 250,  'price' => 4.00],
            4 => ['tokens' => 100,  'price' => 2.00],
        ];
    }

    public function getPackageById(int $id): ?array
    {
        $packages = self::getPackages();
        return $packages[$id] ?? null;
    }

    public function addTokens(int $tokens, int $MemberId): void
    {
        $this->_framework->query("
            INSERT INTO Token (Member_id, balance)
            VALUES ({$MemberId}, {$tokens})
            ON DUPLICATE KEY UPDATE balance = balance + {$tokens}
        ");
    }
}
