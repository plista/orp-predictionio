<?php

namespace Plista\Orp\PredictionIOMatrixFactorization;

use Guzzle\Http\Exception\BadResponseException;
use Plista\Orp\Sdk\Handle;

/**
 * Class PushItem, this class adds new items to the pool
 * @package Plista\Orp\Predictio\Sdk
 */
class PushItem implements Handle {
	private static $path = '/var/www/log/';

	private $label;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var int
	 */
	private $itemId;


	public function validate($item) {
		if (empty($item)) {
			throw new ValidationException('Error: item is empty');
		}

		if (empty($item['id'])) {
			throw new ValidationException('Error: Item ID is empty');
		}

		if (empty($item['domainid'])) {
			throw new ValidationException('Error: Domain ID is empty');
		}

		return true;
	}

	/**
	 * push item notifications to kornakapi
	 * we can use the pio_itypes tag to label items for a domain
	 */
	public function push() {
		$log='';
		$client = $this->model->getClient();
		$command = $client->getCommand('create_item', array('pio_iid' => $this->itemId, 'pio_itypes' => strval($this->label)));
		try{
			$client->execute($command);
		}catch  (BadResponseException $e) {
			#$log.='Caught exception: PushItem Push Failed'.  $e->getMessage(). "\n";
		}
		#file_put_contents( self::$path.'PushItem.log', $log. '------------------------'."\n", FILE_APPEND | LOCK_EX);
	}

	public function invalidateItem(){
		$log='';
		$client = $this->model->getClient();
		$command = $client->getCommand('delete_item', array('pio_iid' => strval($this->itemId)));
		try{
			$client->execute($command);
		}catch (BadResponseException $e){
			#$log.='Caught exception: PushItem invalidate failed'.  $e->getMessage(). "\n";
		}
		#file_put_contents( self::$path.'PushItem.log', $log. '------------------------'."\n", FILE_APPEND | LOCK_EX);
	}

	/**
	 * @param $body
	 * @return mixed
	 */
	public function handle($body) {
		$this->model = new Model();
		$this->itemId= intval($body['id']);
		$this->label = strval($body['domainid']);
		if($body['flag']== 0){ //check for invalidation flag
			$this->push();
		}else{
			$this->invalidateItem();
		}
	}
}