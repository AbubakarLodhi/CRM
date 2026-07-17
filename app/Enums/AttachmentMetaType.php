<?php

namespace App\Enums;

enum AttachmentMetaType: string
{
    case PROFILE_PHOTO = 'profile_photo';
    case MERCHANT_LOGO = 'merchant_logo';
    case BRAND_LOGO = 'brand_logo';
    case BUSINESS_LOGO = 'business_logo';
    case PRODUCT_IMAGE = 'product_image';
    //    case VARIANT_IMAGE = 'variant_image';
    case CATEGORY_ICON = 'category_icon';
    case ASSET_FILE = 'asset_file';
}
