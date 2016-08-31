<?php
require_once('autoload.php');
require_once('iwantabot.inc.php');

use Twitterbot\Lib\Logger;
use Twitterbot\Lib\Config;
use Twitterbot\Lib\Auth;
use Twitterbot\Lib\Ratelimit;
use Twitterbot\Lib\Search;
use Twitterbot\Lib\Block;
use Twitterbot\Lib\Filter;
use Twitterbot\Lib\Retweet;

(new IWantABot)->run();

class IWantABot
{
    public function __construct()
    {
        $this->sUsername = 'IWantABot';
        $this->logger = new Logger;
    }

    public function run()
    {
        //load config from username.json file
        $oConfig = new Config();
        if ($oConfig->load($this->sUsername)) {

            //check rate limit before anything else
            if ((new Ratelimit)->check($oConfig->get('min_rate_limit'))) {

                //check correct username
                if ((new Auth)->isUserAuthed($this->sUsername)) {

                    //search for new tweets
                    $aTweets = (new Search)
                        ->set('oConfig', $oConfig)
                        ->search($oConfig->get('search_strings'));

                    //filter out tweets from blocked accounts
                    $aTweets = (new Block)->filterBlocked($aTweets);

                    //filter out unwanted tweets/users
                    $aTweets = (new Filter)
                        ->set('oConfig', $oConfig)
                        ->setFilters()
                        ->filter($aTweets);

                    die(var_dump(count($aTweets)));
                    if ($aTweets) {
                        //retweet remaining tweets
                        (new Retweet)->post($aTweets);
                    }

                    $this->logger->output('done!');
                }
            }
        }
    }
}