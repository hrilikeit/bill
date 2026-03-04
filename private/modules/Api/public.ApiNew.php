<?php

require '../private/views/WebShowView.php';
require '../private/modules/Purchase/public.Purchase.php';
require '../private/domain/class.StartLiveMeetingData.php';

class ApiNew extends StaysailPublic
{
    protected $framework;
    protected $_db;

    const DYTE_ORG_ID = '4bbaf14e-b71f-43cf-8c85-0f9cd1e373a0';
    const DYTE_API_KEY = '64048e8c6a5524870134';
    const DYTE_API_SECRET = 'Authorization: Basic NGJiYWYxNGUtYjcxZi00M2NmLThjODUtMGY5Y2QxZTM3M2EwOjY0MDQ4ZThjNmE1NTI0ODcwMTM0';

    public function __construct($dbc = '')
    {
//        $this->framework = StaysailIO::engage();
        $this->framework = StaysailIO_PDO::engage();
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');

        switch ($job) {
            case 'start_livestreaming_meeting':
                $data = $this->startLivestreamingMeeting();
                break;
            case 'Entertainer_tips':
                $data = $this->getEntertainerTips();
                break;
            case 'Entertainer_goal':
                $data = $this->getEntertainerGoal();
                break;
            case 'get_Live_Like_Count':
                $data = $this->getLiveLikeCount();
                break;
            case 'add_Live_Like_Count':
                $data = $this->addLiveLikeCount();
                break;
            case 'cancellation_Live_Like_Count':
                $data = $this->cancellationLiveLikeCount();
                break;
            case 'live_tip':
                $data = $this->liveTip();
                break;
            case 'add_thumbnail':
                $data = $this->addThumbnail();
                break;
            case 'get_goal_tip_user':
                $data = $this->getGoalTipUser();
                break;
            case 'get_all_models':
                $data = $this->getAllModels();
                break;
            case 'get_posts':
                $data = $this->getPosts();
                break;
            default:
                $data = ['error' => 'Not Found'];
        }
        echo json_encode($data);
        die;
    }

    private function startLivestreamingMeeting()
    {
        $meetingId = StaysailIO::get('meeting_id');
        $MemberId = $_SESSION['Member.id'];
        $participantName = $_SESSION['Member.name'] ?? 'Guest';

        if (!$MemberId) return ['error' => 'User not logged in'];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.dyte.io/v2/meetings/$meetingId/livestreams",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                Api::DYTE_API_SECRET,
                "Content-Type: application/json"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) return ['error' => $err];

        $livestreamData = json_decode($response, true)['data'] ?? null;
        if (!$livestreamData) return ['error' => 'Cannot create livestream'];
        $startLiveMeetingData = new StartLiveMeetingData();
        $startLiveMeetingData->Member_id = $MemberId;
        $startLiveMeetingData->ingest_server = $livestreamData['ingest_server'];
        $startLiveMeetingData->livestream_id = $livestreamData['id'];
        $startLiveMeetingData->stream_key = $livestreamData['stream_key'];
        $startLiveMeetingData->playback_url = $livestreamData['playback_url'];
        $startLiveMeetingData->save();

        $participantCurl = curl_init();
        curl_setopt_array($participantCurl, [
            CURLOPT_URL => "https://api.dyte.io/v2/meetings/$meetingId/participants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "name" => $participantName,
                "presetName" => "group_call_participant" // ролі: host / participant / livestream
            ]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode(self::DYTE_API_KEY . ":" . self::DYTE_API_SECRET),
                "Content-Type: application/json"
            ],
        ]);
        $participantResp = curl_exec($participantCurl);
        $participantErr = curl_error($participantCurl);
        curl_close($participantCurl);

        if ($participantErr) return ['error' => $participantErr];

        $participantData = json_decode($participantResp, true)['data'] ?? null;
        $authToken = $participantData['authResponse']['authToken'] ?? null;

        if (!$authToken) return ['error' => 'Cannot get authToken'];

        return [
            'livestream' => $livestreamData,
            'authToken' => $authToken
        ];
    }

    private function getEntertainerTips()
    {
        $entertainerId = StaysailIO::get('Entertainer_id');
        $data = [];
        if ($entertainerId) {
            $TipMenuIds = $this->framework->getAllIdsRowsByField('TipMenu',
                'Entertainer_id', $entertainerId);
            foreach ($TipMenuIds as $TipMenuId) {
                $TipMenu = new TipMenu($TipMenuId[0]);
                $data[] = [
                    'name' => $TipMenu->name,
                    'price' => $TipMenu->price
                ];
            }
        }

        return $data;
    }

    private function getEntertainerGoal()
    {
        $entertainerId = StaysailIO::get('Entertainer_id');
        $data = [];
        if ($entertainerId) {
            $filters = array(new Filter(Filter::Match, array('Entertainer_id' => $entertainerId)),
                new Filter(Filter::Sort, 'id DESC'),
            );
            $goalHistory = $this->framework->getSingle('Goal', $filters);

            if($goalHistory && isset($goalHistory->id)){
                $data[] = [
                    'id' => $goalHistory->id,
                    'name' => $goalHistory->name,
                    'price' => $goalHistory->price,
                    'current_count' => $goalHistory->current_count,
                    'status' => $goalHistory->status,
                    'percent ' => intval($goalHistory->price)*intval($goalHistory->current_count)/100
                ];
            }else{
                $data[] = [
                    'id' =>1,
                    'name' => "name",
                    'price' => 999,
                    'current_count' => 999,
                    'status' => 1,
                    'percent ' => 1
                ];
            }

        }
        return $data;
    }

    private function getLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);

        return (int)$webShowDyte->like_count;
    }

    private function addLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);
        $webShowDyte->like_count ++;
        $webShowDyte->save();

        return $webShowDyte->like_count;
    }

    private function cancellationLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);
        $webShowDyte->like_count = 0;
        $webShowDyte->save();

        return $webShowDyte->like_count;
    }

    public function liveTip()
    {
        $amount = $_POST['amount'];
        $FanId = $_SESSION['Member.id'];

        $filter = array(
            new Filter(Filter::Match, array('Entertainer_id' => $_POST['Entertainer_id']))
        );
        $goalIdFromTable = $this->framework->getSingle('Goal', $filter);

        $idGoal = $goalIdFromTable->id;

        $data = [];
        $Member = new Member($FanId);
        $Purchase = new Purchase();
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $FanId)),
            new Filter(Filter::Match, array('default_card' => 1))
        );

        $pm = $this->framework->getSingle('Payment_Method', $filter);


        $amount = intval($amount);
        if ($amount > 0) {
            $Order = new Order();
            $Order->goal_id = $idGoal;
            $Order->setMember($Member);
            $Order->addOrderLine('Fan goal', $amount, 1, $Member);
            list ($ok, $message, $responseText) = $Purchase->validateCharge($Order, '', $pm, true);

            if ($ok) {
                // $goal = new Goal($goalId);
                $goal = new Goal($idGoal);
                $goal->current_count = 1;
                if ($goal->status == 0 ){
                    $goal->current_count = (int)$goal->current_count + $amount;
                    if ($goal->price <= $goal->current_count){
                        $goal->status = 1;
                    }
                    $goal->save();
                }
                $message = ['status' => "Success", 'response_text' => $responseText];
                //  $message = "Success";
            } else {
                $Order->cancel();
                // $message = "Cancel";
                $message = ['status' => "Cancel", 'response_text' => $responseText];
            }
        } else {
            $message = "Amount is zero";
        }

        return $message;
    }

    public function addThumbnail()
    {
        $meetingId = $_POST['meeting_id'];
        $thumb = $_POST['thumb'];
        $filter = array(
            new Filter(Filter::Match, array('meeting_id' => $meetingId))
        );
        $WebShowDyte = $this->framework->getSingle('WebShowDyte', $filter);
        $WebShowDyte->thumbnail = $thumb;
        $WebShowDyte->view_count = 2;
        $WebShowDyte->save();

        return 1;
    }

    public function getGoalTipUser()
    {
        $goalId = StaysailIO::post('goal_id');
        $memberId = StaysailIO::post('Member_id');

        $filter = array(
            new Filter(Filter::Match, array('goal_id' => $goalId, 'Member_id' => $memberId))
        );
        $order = $this->framework->getSingle('Order', $filter);

        if (!$order){
            return false;
        }

        return true;
    }

    private function getAllModels()
    {
        $page = (int) $_REQUEST['page'];
        $limit = 48;
        $offset = ($page * $limit) - $limit;
        $member_id = $_REQUEST['memberId'];
        $Member = new Member($member_id);
        $Fan = $Member->getAccountOfType('Fan');
        $subscriptions =  $Fan->getActiveSubscriptions();
        $subscribedEntertainers = [];

        foreach ($subscriptions as $s) {
            $subscribedEntertainers[] = $s->Entertainer_id;
        }


        $entertainers = $Fan->getActiveEntertainers();

        $data = [];
        if (sizeof($entertainers)) {
            foreach ($entertainers as $entertainer)
            {
                if (!in_array($entertainer->id, $subscribedEntertainers)) {
                    $entertainerData['id'] = $entertainer->id;
                    $entertainerData['member_id'] = $entertainer->Member_id;
                    $entertainerData['name'] = $entertainer->name;
                    $data[] = $entertainerData;
                }
            }
        }

        $pageData = array_slice($data, $offset, $limit);

        $pages = count($data) / $limit;
        $pages = (int) round($pages, 0, PHP_ROUND_HALF_UP);

        return json_encode(["data" => $pageData, "page" => $page, 'pages' => $pages]);
    }

    private function getPosts()
    {
        $page = (int) $_REQUEST['page'];
        $limit = 10;
        $offset = ($page * $limit) - $limit;
        $entertainer_id = $_REQUEST['entertainerId'];
        $Entertainer = new Entertainer($entertainer_id);

        $pagesCount = $Entertainer->getPostsCount();
        $pages = $pagesCount / $limit;
        $pages = (int) round($pages, 0, PHP_ROUND_HALF_UP);
        $posts = $Entertainer->getPostsPagination($offset, $limit);

        $html = '';
        foreach ($posts as $Post) {
            $html .= $Post->getHTML();
        }

        header('Content-Type: application/json');
        return ['data' => $html, "page" => $page, 'pages' => $pages];
    }
}
