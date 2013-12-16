<?php
namespace Plista\Orp\PredictionIOMatrixFactorization;


use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use \Exception;
use Plista\Orp\Sdk\Context;
use Plista\Orp\Sdk\Handle;
use Plista\Orp\Sdk\Recs;
use PredictionIO\UnidentifiedUserException;


/**
 * PredictionIO: Weighted Matrix Factorization
 * this will use matrix factorization to callculate Recommendation.
 * Matrixfactorization aproximates the two matrixes whoes product results in the completed user-item matrix.
 *
 * This is an iterative method, thus the allgroithm is repeated until it converges or stopped.
 *
 *
 * Mathematical Terms
 * - Alternating Least Squares weighted lambda regularization
 *
 * Technology Terms
 * - Collaborative Filtering
 * - Apache Mahout
 * - PredictionIO
 * - HTTP Interface
 * ALS for implicit user rating (what is used here)
 * @link http://www2.research.att.com/~yifanhu/PUB/cf.pdf
 * ALS for exolicit rating
 * @link http://www.hpl.hp.com/personal/Robert_Schreiber/papers/2008%20AAIM%20Netflix/netflix_aaim08%28submitted%29.pdf
 *
 * PredictionIO
 * @link http://prediction.io/
 * PredictionIO uses mahout, check out this document for some usage of mahout
 * @link https://www.ibm.com/developerworks/java/library/j-mahout/j-mahout-pdf.pdf
 *
 */
class Fetch implements Handle {

	private static $path = '/var/www/log/';

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var int
	 */
	private $userid;

	/**
	 * @var int
	 */
	private $itemid;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @var String
	 */
	private $label;


	/**
	 * This class, fetches the recommendations
	 * first if userid is given we ask predictionio for recommendations for this user, based on his item history
	 * if there is no userid  or the user is new (not in the database) but an itemid is given we ask predictionio for recommendations similar to that item.
	 * last we use a fallback item recommendation engine
	 * @return array|Recs
	 */
	public function fetch() {
	//get the recommendations stored by the worker method
		$res=array();
		$userid = $this->userid;
		$log='';

		//check if we have a user id
		if(isset($this->userid)){
			//check if user is allready contained in database, so we can give recommendations for user
			$log.= "\n Userbased\n";
			try{
				$client = $this->model->getClient();
				$client->identify($this->userid);
				$res = $client->execute($client->getCommand('itemrec_get_top_n', array('pio_engine' => 'engine','pio_itypes' => strval($this->label), 'pio_n' => $this->limit)));
				$log.= "\n Recommendations: ".serialize($res) . "\n";
				if(empty($res)){
					$log.="\n empty userbased recommendation for user: ". strval($this->userid). "\n";
				}

			}catch (ClientErrorResponseException  $e){
				$log.= "\nFetch userid Fetch failed\n" . $e->getMessage();
			}catch(ServerErrorResponseException $e2){
				$log.= "\nUserID: Wrong access\n" .$e2->getMessage();
			}catch(UnidentifiedUserException $e3){
				$log.= "\nUnknown user\n" .$e3->getMessage();
			}catch(Exception $ef){
				$log.= "Something bad happend" .$ef->getMessage();
			}

		}else{
			$log.="\n empty username" . "\n";
		}

		if(empty($res) && isset($this->itemid)){
			$log.= "\n Itembased\n";
			try{
				$client = $this->model->getClient();
				$command = $client->getCommand('itemsim_get_top_n', array('pio_engine' => 'engine1','pio_itypes' => strval($this->label), 'pio_iid' =>  $this->itemid, 'pio_n' => $this->limit));
				$res = $client->execute($command);
				$log.= "\n Recommendations: \n".serialize($res) . "\n";
				if(empty($res)){
					$log.="\n empty userbased recommendation for item: ". strval($this->itemid). "\n";
				}

			}catch (ClientErrorResponseException  $e){
				$log.= "\nFetch itemid Fetch failed\n" .$e->getMessage();
			}catch(ServerErrorResponseException $e2){
				$log.= "\nWrong access\n" .$e2->getMessage();
			}catch(Exception $ef){
				$log.= "\nSomething bad happend\n" .$ef->getMessage();
			}
		}elseif(!isset($this->itemid)){
			$log.= "\n ItemID was empty \n";
		}

		if(empty($res)){
			$log.= "\n Fallback \n";
			try{
				$client = $this->model->getClient();
				$client->identify("1");  //Id one is zero id 2.0
				$command = $client->getCommand('itemrec_get_top_n', array('pio_engine' => 'fallback', 'pio_itypes' => strval($this->label),'pio_n' => $this->limit));
				$res = $client->execute($command);
				$log.= "\n Recommendations: ".serialize($res) . "\n";
				if(empty($res)){
					$log.="\n empty fallback recommendation \n";
				}

			}catch (ClientErrorResponseException  $e){
				$log.= "\nFetch itemid Fetch failed\n" .$e->getMessage();
			}catch(ServerErrorResponseException $e2){
				$log.= "\nWrong access\n" .$e2->getMessage();
			}catch(UnidentifiedUserException $e3){
				$log.= "\nUnknown user\n" .$e3->getMessage();
			}catch(Exception $ef){
				$log.= "\nSomething bad happend\n" .$ef->getMessage();
			}
		}

		file_put_contents( self::$path.'Fetch.log', $log. '------------------------'."\n", FILE_APPEND | LOCK_EX);
		if(!empty($res)){
			return $this->normalize($res);
		}

		return array();
	}

	/**
	 * Method that normalizes the scores of the recommendations by predictionio
	 * @param array $itemids
	 * @return array
	 */
	private function normalize($results) {
		$results['result']=$results['pio_iids'];
		foreach($results['result'] as $idx => $value){
			$results['score'][$idx]= 1/($idx+1);
		}
		return $recs = new Recs($results);
	}

	/**
	 * @param $body
	 * @return bool
	 */
	public function validate($body) {
		if(empty($body['limit'])){
			throw new ValidationException('Recommendation requires an item limit!');
		}
	}

	/**
	 * @param $body
	 * @return mixed
	 */
	public function handle($request) {
		$this->limit = $request['limit'];
		$context = new Context($request['context']);
		$this->model = new Model();
		$this->itemid = $context->getItem_source();
		$this->userid =strval($this->model->idMapping($context->getUser_cookie()));
		$this->label = $context->getPublisher();
		return $this->fetch();
	}
}