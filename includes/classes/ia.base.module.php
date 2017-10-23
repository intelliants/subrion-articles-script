<?php
/******************************************************************************
 *
 * Subrion Articles Publishing Script
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion Articles Publishing Script
 *
 * This program is a commercial software and any kind of using it must agree
 * to the license, see <https://subrion.pro/license.html>.
 *
 * This copyright notice may not be removed from the software source without
 * the permission of Subrion respective owners.
 *
 *
 * @link https://subrion.pro/product/publishing.html
 *
 ******************************************************************************/

interface iaPublishingModule
{
    const STATUS_REJECTED = 'rejected';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';

    const COUNTER_ACTION_INCREMENT = '+';
    const COUNTER_ACTION_DECREMENT = '-';
}
