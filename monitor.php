<?php
require_once('queues.php');
require(implode(DIRECTORY_SEPARATOR, array(
    __DIR__,
    'vendor',
    'autoload.php'
)));

use PAMI\Client\Impl\ClientImpl;
use PAMI\Listener\IEventListener;
use PAMI\Message\Event\EventMessage;
use PAMI\Message\Action\ListCommandsAction;
use PAMI\Message\Action\ListCategoriesAction;
use PAMI\Message\Action\CoreShowChannelsAction;
use PAMI\Message\Action\CoreSettingsAction;
use PAMI\Message\Action\CoreStatusAction;
use PAMI\Message\Action\StatusAction;
use PAMI\Message\Action\ReloadAction;
use PAMI\Message\Action\CommandAction;
use PAMI\Message\Action\HangupAction;
use PAMI\Message\Action\LogoffAction;
use PAMI\Message\Action\AbsoluteTimeoutAction;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\BridgeAction;
use PAMI\Message\Action\CreateConfigAction;
use PAMI\Message\Action\GetConfigAction;
use PAMI\Message\Action\GetConfigJSONAction;
use PAMI\Message\Action\AttendedTransferAction;
use PAMI\Message\Action\RedirectAction;
use PAMI\Message\Action\DAHDIShowChannelsAction;
use PAMI\Message\Action\DAHDIHangupAction;
use PAMI\Message\Action\DAHDIRestartAction;
use PAMI\Message\Action\DAHDIDialOffHookAction;
use PAMI\Message\Action\DAHDIDNDOnAction;
use PAMI\Message\Action\DAHDIDNDOffAction;
use PAMI\Message\Action\AgentsAction;
use PAMI\Message\Action\AgentLogoffAction;
use PAMI\Message\Action\MailboxStatusAction;
use PAMI\Message\Action\MailboxCountAction;
use PAMI\Message\Action\VoicemailUsersListAction;
use PAMI\Message\Action\PlayDTMFAction;
use PAMI\Message\Action\DBGetAction;
use PAMI\Message\Action\DBPutAction;
use PAMI\Message\Action\DBDelAction;
use PAMI\Message\Action\DBDelTreeAction;
use PAMI\Message\Action\GetVarAction;
use PAMI\Message\Action\SetVarAction;
use PAMI\Message\Action\PingAction;
use PAMI\Message\Action\ParkedCallsAction;
use PAMI\Message\Action\SIPQualifyPeerAction;
use PAMI\Message\Action\SIPShowPeerAction;
use PAMI\Message\Action\SIPPeersAction;
use PAMI\Message\Action\SIPShowRegistryAction;
use PAMI\Message\Action\SIPNotifyAction;
use PAMI\Message\Action\QueuesAction;
use PAMI\Message\Action\QueueStatusAction;
use PAMI\Message\Action\QueueSummaryAction;
use PAMI\Message\Action\QueuePauseAction;
use PAMI\Message\Action\QueueRemoveAction;
use PAMI\Message\Action\QueueUnpauseAction;
use PAMI\Message\Action\QueueLogAction;
use PAMI\Message\Action\QueuePenaltyAction;
use PAMI\Message\Action\QueueReloadAction;
use PAMI\Message\Action\QueueResetAction;
use PAMI\Message\Action\QueueRuleAction;
use PAMI\Message\Action\MonitorAction;
use PAMI\Message\Action\PauseMonitorAction;
use PAMI\Message\Action\UnpauseMonitorAction;
use PAMI\Message\Action\StopMonitorAction;
use PAMI\Message\Action\ExtensionStateAction;
use PAMI\Message\Action\JabberSendAction;
use PAMI\Message\Action\LocalOptimizeAwayAction;
use PAMI\Message\Action\ModuleCheckAction;
use PAMI\Message\Action\ModuleLoadAction;
use PAMI\Message\Action\ModuleUnloadAction;
use PAMI\Message\Action\ModuleReloadAction;
use PAMI\Message\Action\ShowDialPlanAction;
use PAMI\Message\Action\ParkAction;
use PAMI\Message\Action\MeetmeListAction;
use PAMI\Message\Action\MeetmeMuteAction;
use PAMI\Message\Action\MeetmeUnmuteAction;
use PAMI\Message\Action\EventsAction;
use PAMI\Message\Action\VGMSMSTxAction;
use PAMI\Message\Action\DongleSendSMSAction;
use PAMI\Message\Action\DongleShowDevicesAction;
use PAMI\Message\Action\DongleReloadAction;
use PAMI\Message\Action\DongleStartAction;
use PAMI\Message\Action\DongleRestartAction;
use PAMI\Message\Action\DongleStopAction;
use PAMI\Message\Action\DongleResetAction;
use PAMI\Message\Action\DongleSendUSSDAction;
use PAMI\Message\Action\DongleSendPDUAction;

define("EXT_STATUS_RING", 8);
define("EXT_STATUS_IDLE", 0);
define("EXT_STATUS_CALLING", 1);
define("EXT_HOLD", 16);
define("CHANNEL_CONNECTED", 6);

class Monitor implements IEventListener
{
    private $agents = array();

    function Monitor()
    {
        $agents = get_all_agents();
        foreach($agents as $agent) {
            $this->agents[$agent]['state'] = 0;
            $this->agents[$agent]['in'] = 0;
            $this->agents[$agent]['out'] = 0;
            $this->agents[$agent]['start'] = 0;
            $this->agents[$agent]['uptime'] = 0;
            $this->agents[$agent]['calls'] = 0;
        }
    }

    public function dump_agents($fd)
    {
        if (!isset($this->agents)) return;
            
        fwrite($fd, "\n");
        foreach($this->agents as $agent => $status) {
            fwrite($fd, "$agent:");
            foreach($status as $items => $s) {
                fwrite ($fd, "$items=>$s ");
            }
            fwrite($fd, "\n");
        }
        fwrite($fd, "\n");
    }

    public function save_status()
    {
        $fd = fopen("ext.tmp", "w") or die("Failed to create file:");
        $this->dump_agents($fd);        
    }

    public function handle(EventMessage $event)
    {
        $name = $event->getName();
        
        if ($name == 'Newstate') {
            $channel = $event->getChannel();
            $ext = explode('-', explode('/', $channel)[1])[0];
            if ($event->getChannelState() == CHANNEL_CONNECTED) { 
                $this->agents[$ext]['uptime'] = time();
                $this->agents[$ext]['calls'] ++;
            }
            
        } else if ($name == 'ExtensionStatus') {
            $ext = $event->getExtension();
            
            if ($event->getStatus() == EXT_STATUS_CALLING
            && $this->agents[$ext]['state'] == EXT_STATUS_IDLE) { 
                $this->agents[$ext]['out'] ++;
            }

            if ($this->agents[$ext]['state'] != $event->getStatus()) {
                $this->agents[$ext]['start'] = time();
                $this->agents[$ext]['state'] = $event->getStatus();
            }

            if ($event->getStatus() == EXT_STATUS_RING) //ringing
                $this->agents[$ext]['in'] ++;
        } else if ($name == 'Newexten' && strstr($event->getApplicationData(), 'Blind Transfer')) {
        } else {
            //echo $name;
            //echo "\n";
            return;
        }        
        $this->dump_agents(STDIN);
        $this->save_status();
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

try
{
	$monitor = new ClientImpl(get_options());
	$monitor->registerEventListener(new Monitor());
	$monitor->open();

	$time = time();
	while(true){
        usleep(1000);
	    $monitor->process();
	}
	$monitor->close(); 
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}
