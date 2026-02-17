<?php
/**
 * PSR-4 case-sensitive alias
 * Linux filesystems are case-sensitive: "ProductTab" != "Producttab"
 * This file creates the properly-cased class name as an alias
 * for backward compatibility with CMS blocks/widgets referencing
 * Rokanthemes\ProductTab\Block\ProductTab (uppercase T)
 */
namespace Rokanthemes\ProductTab\Block;

class ProductTab extends Producttab
{
    // Alias class — all functionality inherited from Producttab
}
