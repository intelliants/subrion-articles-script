<?php
//##copyright##

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

abstract class abstractPublishingModuleAdmin extends abstractModuleAdmin implements iaPublishingModule
{
	protected $_moduleName = 'publishing';
}

abstract class abstractPublishingPackageFront extends abstractModuleFront implements iaPublishingModule
{
	protected $_moduleName = 'publishing';
}