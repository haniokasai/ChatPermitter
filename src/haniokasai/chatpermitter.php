<?php
/**
 * Created by PhpStorm.
 * User: htek
 * Date: 2017/07/07
 * Time: 19:47
 */

namespace haniokasai;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class chatpermitter extends PluginBase implements Listener
{
    /**
     *読み込み部分
     */
    public function onEnable()
    {
        Server::getInstance()->getLogger()->info("Chatpermitter");
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);

        if(!file_exists($this->getDataFolder())){//configを入れるフォルダが有るかチェック
            mkdir($this->getDataFolder(), 0744, true);//なければフォルダを作成
        }

        global $config_pl;//マップの名前と座標を入力します。
        $config_pl = new Config($this->getDataFolder() . "config.yml", Config::YAML,
            array("player名"=>"日付",
                "@chaturl"=>"https://mirm.info/viewkey.php",
                "@deleteurl"=>"https://mirm.info/chat/removekey.php",
                "@deleteday"=>3)
        );

        global $chaturl;
        $chaturl = $config_pl->get("@chaturl");

        global $delete_url;
        $delete_url = $config_pl->get("@deleteurl");

        global $delete_day;
        $delete_day = $config_pl->get("@deleteday");

        global $chatplayers;
        $chatplayers = array();
    }

    public function JoinEv(PlayerJoinEvent $event){
        global /** @var Config $config_pl */
        $config_pl;
        global  /** @var array $chatplayers */
        $chatplayers;

        global $delete_day;

        $name = $event->getPlayer()->getName();
        if($config_pl->exists($name)){
            if(time()-$config_pl->get($name) <= $delete_day*3600*24){
                $chatplayers[$name]=true;
            }else{
                $chatplayers[$name]=false;
            }
        }else{
            $chatplayers[$name]=false;
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        global /** @var array $chatplayers */
        $chatplayers;

        global $chaturl, $delete_url;
        $name = $event->getPlayer()->getName();
        if (!$chatplayers[$name]) {
            echo $event->getMessage();
            if (strlen($event->getMessage()) == 5) {
                $code = preg_replace('/[^a-z]/', '', $event->getMessage());
                $this->getServer()->getScheduler()->scheduleAsyncTask($job4 = new thread_getdata($code, $name, $delete_url));
            } else {
                $event->getPlayer()->sendMessage("[ChatPermitter] チャットをするには、{$chaturl}にアクセスして、そこで得たキーをチャットに直接入力してください");
            }
            $event->setCancelled();
        }
    }


    public function onCmd(PlayerCommandPreprocessEvent $event){
        $player=$event->getPlayer();
        if($event->getMessage())
        global /** @var array $chatplayers */
        $chatplayers;

        global $chaturl;
        $name = $player->getName();
        $message = $event->getMessage();
        $command = substr($message, 1);
        $args = explode(" ", $command);
        switch ($args[0]) {
            case "me":
                if (!$chatplayers[$name]) {
                    $player->sendMessage("[ChatPermitter] このコマンドを使うには、{$chaturl}にアクセスして、そこで得たキーをチャットに直接入力してください");
                    $event->setCancelled(true);
                }
        }

        }

    }

/**
 * @property  String player
 */
class thread_getdata extends AsyncTask
{
    public function __construct($code,$player,$delete_url)
    {
        $this->code=$code;
        $this->player=$player;
        $this->delete_url=$delete_url;
    }
    public function onRun()
    {
        $re=array();
        $re["playername"]=$this->player;


        //http://qiita.com/re-24/items/bfdd533e5dacecd21a7a
        $base_url = $this->delete_url;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $base_url.'?key='.$this->code);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);   // ヘッダーも出力する

        $response = curl_exec($curl);

        //var_dump($response);

// ステータスコード取得
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// header & body 取得

        if(is_numeric($response)){
            if($response==0){
                $re["result"]=false;
            }else{
                $re["result"]=true;
            }
        }else{
            $re["result"]=false;
            echo "Error Occured :".PHP_EOL;
            echo $response.PHP_EOL;
            echo $code.PHP_EOL;
        }

        curl_close($curl);
        //var_dump($re);
        $this->setResult($re);
    }

    public function onCompletion(Server $server)
    {
        /** @var ResultSet $re */
        $re=$this->getResult();
        //var_dump($re);

        /** @var Player $player */
        $pl = $re["playername"];
        //var_dump($server->getPlayer($pl));
        if($server->getPlayer($pl)!==null){
            //var_dump($server->getPlayer($pl));
            $player = $server->getPlayer($pl);
            //$player->sendMessage("you are online");
            $name=$player->getName();
            if($re["result"]){
                global  /** @var array $chatplayers */
                $chatplayers;
                $chatplayers[$name]=true;

                global /** @var Config $config_pl */
                $config_pl;
                if($config_pl->exists($name)){
                    $config_pl->remove($name);
                }

                $config_pl->set($name,time());
                $config_pl->save();

                $player->sendMessage("[ChatPermitter]コードが認証されました!");
            }else{
                $player->sendMessage("[ChatPermitter]コードが間違っているはずです!");
            }

        }else{
            //$player->sendMessage("you are not online");
        }
    }
}

class ResultSet
{
    public $player;
    public $result;
}
