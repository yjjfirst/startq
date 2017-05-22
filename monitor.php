<?php
require_once('options.php');
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
use PAMI\Message\Event\AgentCalledEvent;
use PAMI\Message\Event\AgentRingNoAnswerEvent;
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
define("EXT_STATUS_HOLD", 16);

class QueueCaller
{
    private $start_time;
    private $end_time;
    private $queue;

    public function __construct($start, $queue)
    {
        $this->start_time = $start;
        $this->queue = $queue;
    }

    public function set_end_time($time)
    {
        $this->end_time = $time;
    }

    public function get_queue()
    {
        return $this->queue;
    }

    public function get_duration()
    {    

        return $this->end_time - $this->start_time;
    }
}

class QueueAverage
{
    private $average;
    private $counts;
    private $queue;

    public function __construct($average, $counts, $queue)
    {
        $this->average = $average;
        $this->counts = $counts;
        $this->queue = $queue;
    }

    public function add_call($caller)
    {
        $privious = $this->average * $this->counts;
        $total = $privious + $caller->get_duration();

        $this->counts += 1;
        $this->average = intval($total/$this->counts);
    }

    public function save($fd)
    {
        fwrite($fd, "$this->queue:$this->average\n");
    }

    public function get_queue()
    {
        return $this->queue;
    }
}

class Monitor implements IEventListener
{
    private $queues_vm = array();
    private $queues_status;
    private $queues_origin;
    private $callers;
    private $queues_average;
    
    private $sem_id;

    function get_queues_vm()
    {
        $queues_vm = get_vm_options();
        return $queues_vm;
    }
    
    public function __construct()
    {

        $this->queues_status = $this->init_queues_status();
        $this->queues_origin = $this->init_queues_origin();
        $this->init_queues_average();

        $this->queues_vm = $this->get_queues_vm();
        foreach($this->queues_vm as $key => $vm) {
            $this->queues_vm[$key] = 0;            
        }

        $SEMKEY = 1; 
        $this->sem_id = sem_get($SEMKEY, 1);
        
        $this->save_status();
        $this->save_vm();
        $this->save_average();
        
        unlink(LONGEST_WAIT_FILE);
    }

    public function init_queues_average()
    {
        $queues = get_all_queues();
        foreach ($queues as $queue) {
            $this->queues_average[$queue] =
                new QueueAverage(0, 0, $queue);
        }
    }

    public function init_queues_origin()
    {
        $queues = get_all_queues();
        $queues_origin = array();

        foreach($queues as $queue) {
            $status = internal_get_queue_status($queue);
            $queues_origin[$queue] = array();
            $queues_origin[$queue]['in'] = $status['inbound_calls'];
            $queues_origin[$queue]['answered'] = $status['answered_calls'];
            $queues_origin[$queue]['abandoned'] = $status['abandoned_calls'];            
        }
        return $queues_origin;
    }

    public function init_agent(&$agent_status, $agent)
    {
        $agent_status[AGENT_STATE] = get_agent_init_status($agent);
        $agent_status[AGENT_STARTTIME] = time();
        $agent_status[AGENT_STATE_DURATION] = 0;
        $agent_status[AGENT_IN] = 0;
        $agent_status[AGENT_OUT] = 0;
        $agent_status[AGENT_ANSWERED_CALLS] = 0;
        $agent_status[AGENT_BOUNCED_CALLS] = 0;
        $agent_status[AGENT_TRANSFERED_CALLS] = 0;
        $agent_status[AGENT_AVERAGE_TALK_TIME] = 0;
        $agent_status[AGENT_UPTIME] = 0;
        $agent_status[AGENT_UPCALLS] = 0;
    }

    public function init_queues_status()
    {

        $queues = get_all_queues();
        $queues_status = array();
                
        foreach($queues as $queue) {
            $queue_status[$queue]  = array();
            $agents_in_queue = get_all_agents_in_queue($queue);
            foreach($agents_in_queue as $agent) {
                $queues_status[$queue][$agent] = array();
                $agent_status = &$queues_status[$queue][$agent];
                $this->init_agent($agent_status, $agent);
            }
            
        }
        return $queues_status;
    }

    public function dump_all_queues($fd)
    {
        //echo "*******************************************************************\n";
        $queues = get_all_queues();
        foreach($queues as $queue) {
            $this->dump_one_queue($queue, $fd);
            fwrite($fd, "\n");
        }
    }

    public function dump_one_queue($name, $fd)
    {
        $in = $this->queues_origin[$name]['in'];
        $answered = $this->queues_origin[$name]['answered'];
        $abandoned = $this->queues_origin[$name]['abandoned'];
        fwrite($fd, "queue=$name in=$in answered=$answered abandoned=$abandoned\n");
        $this->dump_agents($name, $fd);
    }
    
    public function dump_agents($queue, $fd)
    {
        if (!isset($this->queues_status[$queue])) return;

        $agents = $this->queues_status[$queue];
        foreach($agents as $agent => $status) {
            fwrite($fd, "$agent:");
            foreach($status as $items => $s) {
                fwrite ($fd, "$items=$s ");
            }
            fwrite($fd, "\n");
        }
    }

    public function save_status()
    {
        //$this->dump_all_queues(STDOUT); 
        
        sem_acquire($this->sem_id);

        $fd = fopen(QUEUES_STATUS_FILE, "w") or die("Failed to create file:");
        if (flock($fd, LOCK_EX)) {            
            $this->dump_all_queues($fd);
            flock($fd, LOCK_UN);
        }

        sem_release($this->sem_id);
    }

    public function get_event_ext($event)
    {
        $name = $event->getName();
        $ext = '';
        
        if ($name == 'Newstate') {
            $channel = $event->getChannel();
            $channel_vars = explode('/', $channel);
            $ext_vars = explode('-', $channel_vars[1]);
            $ext = $ext_vars[0];
        } else if ($name == 'ExtensionStatus') {
            $ext = $event->getExtension();
        } else if (strstr($event->getName(), "MemberStatus")) {
            $ext = get_agent_extension($event);
        } else if (strstr($event->getName(), "Agent")) {
            $ext = get_agent_extension($event);
        }

        return $ext;
    }

    private function get_ext_from_channel($channel)
    {
        $channel_var = trim($channel);
        if (empty($channel_var)) 
            return NULL;

        $channel_vars = explode('/', $channel);
        $ext_vars = explode('-', $channel_vars);
        $ext = $ext_vars[0];

        return $ext;
    }

    public function cal_average($caller)
    {
        foreach($this->queues_average as &$average) {
            if ($average->get_queue() != $caller->get_queue()) continue;

            $average->add_call($caller);            
        }
    }
    
    public function inc_transfered_call($ext)
    {
        $queues = get_all_queues();

        foreach($queues as $queue) {
            $agents_in_queue = get_all_agents_in_queue($queue);            
            foreach($agents_in_queue as $agent) {
                if ($agent != $ext) continue;
                $this->queues_status[$queue][$ext][AGENT_TRANSFERED_CALLS] ++;
            }
            
        }
    }

    public function count_transfered_call($event)
    {
        $data_array = explode(',', $event->getApplicationData());

        $blind_transfer = explode(':', $data_array[0]);
        $ext = $this->get_ext_from_channel($blind_transfer[1]);

        if ($ext) {
            $this->inc_transfered_call($ext);
        }

        $user = explode(':', $data_array[2]);
        if (strstr($event->getChannel(), 'xfer')) {
            $this->inc_transfered_call(trim($user[1]));
        }
        
    }

    public function handle_state_change($event, $ext)
    {
        $queue = $event->getQueue();
        $agent = &$this->queues_status[$queue][$ext];

        if ($event->getStatus() == RAW_AGENT_TALK) {
            if ($agent[AGENT_STATE] == RAW_AGENT_AVAILABLE){
                $agent[AGENT_STARTTIME] = time();
                $agent[AGENT_OUT] ++;
            }
        }
        else {
            $agent[AGENT_STARTTIME] = time();
        }

        if ($event->getPause() == 1) {
            $agent[AGENT_STATE] = RAW_AGENT_PAUSED;
            return;
        }
        
        if ($agent[AGENT_STATE] != $event->getStatus()) {
            $agent[AGENT_STATE] = $event->getStatus();
        }
    }

    public function computer_average_talktime($event, $ext)
    {
        $queue = $event->getQueue();
        $agent = &$this->queues_status[$queue][$ext];

        if ($agent[AGENT_UPTIME] != 0) {
            $duration = time() - $agent[AGENT_UPTIME];
            $total =
                $agent[AGENT_AVERAGE_TALK_TIME]
                * ($agent[AGENT_UPCALLS] - 1)
                + $duration;
            $average = $total / $agent[AGENT_UPCALLS];

            $agent[AGENT_UPTIME] = 0;
            $agent[AGENT_AVERAGE_TALK_TIME] = (int)$average;
        }
    }

    private function save_vm()
    {
        $queues_vm = $this->get_queues_vm();
        $fd = fopen(QUEUE_VM_FILE, "w") or die("Failed to create file:");

        foreach($queues_vm as $key => $queue_vm) {
            $count = $this->queues_vm["$key"];
            fwrite($fd, "$key:");
            fwrite($fd, "$count");
            fwrite($fd,"\n");
        }

        fclose($fd);
    }

    private function save_average()
    {
        $fd = fopen(QUEUES_AVERAGE_FILE, "w");
        
        foreach($this->queues_average as $average) {
            $average->save($fd);
        }
    }
    
    public function count_voicemail($event)
    {
        $queues_vm = get_queues_vm();
        foreach($queues_vm as $key => $queue_vm) {
            $data = $event->getApplicationData();
            $vms = explode('@', $data);
            $vm = $vms[0];
            if ($queue_vm == $vm) {
                $this->queues_vm["$key"] ++;
            }

        }

        $this->save_vm();
    }

    public function possible_transfer($event)
    {
        return strstr($event->getApplicationData(), 'Blind Transfer');
    }

    public function handle(EventMessage $event)
    {
        $name = $event->getName();
        $ext = $this->get_event_ext($event);
        $queue;
        
        if (strstr ($name, "Queue") || strstr($name, "Agent")) {
            $queue = $event->getQueue();
            echo "$name\n";
        }
        
        if (!empty($ext) && !$this->if_agent_login_queue($ext)) {
            return;
        }

        if ($name == 'AgentRingNoAnswer') {
            $agent = &$this->queues_status[$queue][$ext];
            $agent[AGENT_BOUNCED_CALLS] ++;                
        }
        else if ($name == 'AgentConnect') {
            $agent = &$this->queues_status[$queue][$ext];

            $agent[AGENT_UPTIME] = time();
            $agent[AGENT_UPCALLS] ++;                
            $agent[AGENT_ANSWERED_CALLS] ++;            
        }
        else if ($name == 'AgentComplete') {
            $agent = &$this->queues_status[$queue][$ext];
            $this->computer_average_talktime($event, $ext);
        }
        else if ($name == 'AgentCalled') {
            $agent = &$this->queues_status[$queue][$ext];
            $agent[AGENT_IN] ++;
        }
        else if ($name == "QueueCallerJoin") {
            $caller = new QueueCaller(time(),$queue);
            $this->callers[$event->getUniqueId()] = $caller;
        }
        else if ($name == "QueueCallerLeave") {
            $caller = $this->callers[$event->getUniqueId()];
            if (!empty($caller)) {
                $caller->set_end_time(time());
                $this->cal_average($caller);
                $this->save_average();
            }
        }
        else if ($name == 'QueueMemberAdded') {
            $this->queues_status[$queue][$ext] = array();
            $this->init_agent($this->queues_status[$queue][$ext], $ext);
        }
        else if ($name == 'QueueMemberStatus') {
            $this->handle_state_change($event, $ext);
        }
        else if ($name == 'Newexten') {
            if ($this->possible_transfer($event)) {
                $this->count_transfered_call($event);
            }
            else if ($event->getApplication() == 'VoiceMail') {
                $this->count_voicemail($event);
            }
            else {
                return;
            }
        } 
        else {
            return;
        }
        
        $this->save_status();
    }

    function if_agent_login_queue($extension)
    {
        $status_events = get_all_queues_status(get_options());
        
        foreach($status_events as $event) {
            if ($event->getName() != 'QueueMember') continue;
            if (get_agent_extension($event) == $extension)
                return TRUE;
        }        
        return FALSE;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$monitor = new ClientImpl(get_options());
$monitor->registerEventListener(new Monitor());

$time = time();
while(true){
    $monitor->open();
    usleep(200000);
    try {
        $monitor->process();
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
    $monitor->close(); 
}
