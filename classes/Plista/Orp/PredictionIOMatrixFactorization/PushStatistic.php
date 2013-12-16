<?php
namespace Plista\Orp\PredictionIOMatrixFactorization;

use Guzzle\Http\Exception\BadResponseException;
use Plista\Orp\Sdk\Handle;
use Plista\Orp\Sdk\Context;
use PredictionIO\UnidentifiedUserException;

/**
 * Class PushStatistic, this class updates the itemstatistics for the useres
 * @package Plista\Orp\orp-sdk-php\KornakapiMatrixFactorization
 */
class PushStatistic implements Handle {
	protected $supported_action = array('impression', 'click');

	private static $path = '/var/www/log/';

	/**
	 * @var int
	 */
	public $itemid = 0;

	/**
	 * @var int
	 */
	public $itemsource;

	/**
	 * optional
	 * @var int
	 */
	public $userid = 0;

	/**
	 * @var Model
	 */
	protected $model;



	public function validate($body) {
		// checking if body contains a notification type
		// additionally one is able to differentiate between a click, impression, engagement and cpo
		// for futher details may have a look at the controller gateway for notification types
		if (empty($body['type'])) {
			throw new ValidationException('Error: empty notification type');
		}
		if (empty($body['context'])) {
			throw new ValidationException('Error: there is no valid context provides in the body.');
		}
		return true;
	}


	/**
	 * Method that registers clicks and impressions on item
	 */
	public function push($mode) {
		$log='';
		//check if user allready exists
		$client = $this->model->getClient();
		try{
			$command = $client->getCommand("get_user", array('pio_uid' => $this->userid));
			$res =$client->execute($command);
		}catch(BadResponseException $e){
			//if not create one
			$command = $client->getCommand('create_user', array('pio_uid' => $this->userid));
			$client->execute($command);
		}catch(UnidentifiedUserException $e2){
			$log.='Caught exception: PushStatistic Failed, unkown user'.  $e2->getMessage(). "\n";
		}catch(\Exception $e3){
			$log.='Caught exception:Something bad happend'.  $e3->getMessage(). "\n";
		}

		try{
			$client->identify($this->userid);
			if($mode === 'click'){
				$client->execute($client->getCommand('record_action_on_item', array('pio_action' => 'conversion', 'pio_iid' => $this->itemsource)));
				$client->execute($client->getCommand('record_action_on_item', array('pio_action' => 'view', 'pio_iid' => $this->itemid)));
			}else{
				$client->execute($client->getCommand('record_action_on_item', array('pio_action' => 'view', 'pio_iid' => $this->itemid)));
			}
		}catch(BadResponseException $e){
			$log.='Caught exception: PushStatistic Failed'.  $e->getMessage(). "\n";
		}catch(UnidentifiedUserException $e2){
			$log.='Caught exception: PushStatistic Failed, unkown user'.  $e2->getMessage(). "\n";
			$command = $client->getCommand('create_user', array('pio_uid' => $this->userid));
			$client->execute($command);
		}catch(\Exception $e3){
			$log.='Caught exception:Something bad happend'.  $e3->getMessage(). "\n";
		}

		file_put_contents( self::$path.'PushStatistic.log', $log. '------------------------'."\n", FILE_APPEND | LOCK_EX);
	}



	/**
	 * @param $body
	 * @return mixed
	 */
	public function handle($body) {
		$context = new Context($body['context']);
		$this->model = new Model();
		$this->userid = strval($this->model->idMapping($body['context']['simple']['57']));

		if(isset($body['recs']['ints'][3][0])){	//if click
			$this->itemid = $body['recs']['ints'][3][0];
			$this->itemsource = isset($body['context']['simple'][25]) ? $body['context']['simple'][25] : 0 ;
			$this->push('click');
		}
		elseif($context->getItem_source()){	//if impression
			$this->itemid =$context->getItem_source();
			$this->push('impression');
		}
		/**
		 * uncomment for PushStatistic log
		 */

		$today = date("m.d.y");
		$res = file_put_contents( self::$path.'PushStatistic_' . $today . '.log', serialize($body) . "\n", FILE_APPEND | LOCK_EX);

		if (!$res) {
			throw new \Exception('Error: Unable to write to statistic file :(');
		}
	}
}