<?php
declare(strict_types = 1);
namespace NetherUpdate\item\enchantment;
use pocketmine\item\enchantment\Enchantment as PMEnch;
class Enchantment extends PMEnch{
    public const SLOT_CROSSBOW = 0x10000;
    public static function init(): void{
        // param: (id, translation, rarity, primaryItem, secondaryItem, maxLevel)
        self::registerEnchantment(new Enchantment(self::QUICK_CHARGE, "%enchantment.crossbowQuickCharge", self::RARITY_RARE, self::SLOT_CROSSBOW, self::SLOT_NONE, 3));
        self::registerEnchantment(new Enchantment(self::PIERCING, "%enchantment.crossbowPiercing", self::RARITY_COMMON, self::SLOT_CROSSBOW, self::SLOT_NONE, 4));
        self::registerEnchantment(new Enchantment(self::MULTISHOT, "%enchantment.crossbowMultishot", self::RARITY_RARE, self::SLOT_CROSSBOW, self::SLOT_NONE, 1));
        self::registerEnchantment(new Enchantment(self::SOUL_SPEED, "%enchantment.soulSpeed", self::RARITY_MYTHIC, self::SLOT_NONE, self::SLOT_FEET, 3));
        self::registerEnchantment(new Enchantment(self::DEPTH_STRIDER, "%enchantment.depthStrider", self::RARITY_RARE, self::SLOT_FEET, self::SLOT_NONE, 3));
        self::registerEnchantment(new Enchantment(self::CHANNELING, "%enchantment.tridentChanneling", self::RARITY_MYTHIC, self::SLOT_TRIDENT, self::SLOT_NONE, 1));
        self::registerEnchantment(new ImpalingEnchantment(self::IMPALING, "%enchantment.tridentImpaling", self::RARITY_RARE, self::SLOT_TRIDENT, self::SLOT_NONE, 5));
        self::registerEnchantment(new Enchantment(self::RIPTIDE, "%enchantment.tridentRiptide", self::RARITY_RARE, self::SLOT_TRIDENT, self::SLOT_NONE, 3));
        self::registerEnchantment(new Enchantment(self::LOYALTY, "%enchantment.tridentLoyalty", self::RARITY_UNCOMMON, self::SLOT_TRIDENT, self::SLOT_NONE, 3));
    }
}