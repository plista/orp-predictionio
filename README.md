ORP-PredictionIO
================

This is an easy-to-use client to utilize PredictionIO for ORP


Setup:

**1. Install PredictionIO**

an installation guide can be found here: http://docs.prediction.io/current/installation/index.html

**2. Download orp-predictionio
`https://github.com/KukumavMozolo/orp-predictionio.git`<br>


**3. Create Composer Project**
`cd orp-predictionio`<br>
`vi composer.json`<br>
and copy:
`{
    "require": {
        "predictionio/predictionio": "~0.6.0"
    }
}`<br>

**4. Install Composer**
`curl -sS https://getcomposer.org/installer | php -d detect_unicode=Off`<br>

**5. Install Dependencys with Composer**
`php composer.phar install`<br>

**6. Download the orp-sdk**
`cd vendor`<br>
`git clone https://github.com/plista/orp-sdk-php.git`<br>

**7. Configure PredictionIO using the PredictionIO webinterface**
Connect to the predictionio webinterface using you browser.
Create a new App.
Copy-paste the App key to ../orp-predictionIO/classes/Plista/Orp/PredictionIOMatrixFactorization/Model.php
$client = PredictionIOClient::factory(array("appkey" => "insert key here"));

**8. Setup Recommender**
This orp-predictionio client assumes that you created two Item Recommendation Engines and one item Similarity Engine.
You will have to adjust  ../orp-predictionIO/classes/Plista/Orp/PredictionIOMatrixFactorization/Fetch.php
such that it fits to the Item Recommendation Engines you created.