<?php
//##copyright##

interface iaPublishingPackage
{
	const STATUS_REJECTED = 'rejected';
	const STATUS_HIDDEN = 'hidden';
	const STATUS_SUSPENDED = 'suspended';
	const STATUS_DRAFT = 'draft';
	const STATUS_PENDING = 'pending';

	const COUNTER_ACTION_INCREMENT = '+';
	const COUNTER_ACTION_DECREMENT = '-';
}

abstract class abstractPublishingPackageAdmin extends abstractPackageAdmin implements iaPublishingPackage
{
	protected $_packageName = 'publishing';
}

abstract class abstractPublishingPackageFront extends abstractPackageFront implements iaPublishingPackage
{
	protected $_packageName = 'publishing';
}