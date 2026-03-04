<?php

require '../private/tools/WebCamDyte.php';

class PublicLive extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;

    public function __construct($dbc = '')
    {
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } elseif ($this->Member) {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $map = Maps::getWebShowMap();
        $header = new HeaderView();
        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('A', 'action',''),
        );

        switch ($job) {
            case 'add_guest_participant':
                $this->addGuestParticipant();
                break;

            case 'activate':
                $this->activate();
                break;
        }
        $writer = new StaysailWriter();
        $writer->addHTML('<h2>Public Live</h2>');
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.dyte.io/v2/livestreams?exclude_meetings=false&status=LIVE",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic ZTQ4ZWJhOGUtMDJlZS00ZTNmLWFkN2YtZTFmZTQ5MjhhYTI1OjA3OWQ0ZjMyZTgzNDQyODQ0Y2Ey",
                "Content-Type: application/json"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $meetings = [];
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $streams = json_decode($response, true);
            foreach ($streams['data'] as $stream){
                $meetings[] = $stream['meeting_id'];
            }
        }
        $filter = new Filter(Filter::Match, array('is_deleted' => 0));
        $Entertainers = $this->framework->getSubset('Entertainer', $filter);
        foreach ($Entertainers as $entertainer) {
            $filters = array(
                new Filter(Filter::Match, array('Entertainer_id' => $entertainer->id)),
                new Filter(Filter::Match, array("public_live_status" => 1)),
                new Filter(Filter::Match, array('show_type' => 2)),
                new Filter(Filter::Sort, 'id DESC'),
            );
            $WebShowDyte = $this->framework->getSingle('WebShowDyte', $filters);
            if ($WebShowDyte){
                if(in_array($WebShowDyte->meeting_id, $meetings)){
                    $writer->addHTML($entertainer->getLiveLink());
                }
                else{
                    if (strtotime("now") > $WebShowDyte->update_at+(60*3)){
                        $WebShowDyte->public_live_status = 0;
                        $WebShowDyte->save();
                    }
                }
            }
        }
        $publicLiveUsers =  $writer->getHTML();
        $containers[] = new StaysailContainer('C', 'content', $publicLiveUsers);
        $layout = new StaysailLayout($map, $containers);

        return $layout->getHTML();
    }

    public function addGuestParticipant()
    {
        $EntertainerId = StaysailIO::post('entertainer_id');
        $meetingId = StaysailIO::post('meeting_id');
        $WebShow = new WebCamDyte();
        $webShowDyte = new WebShowDyte($meetingId);
        $participantData = $WebShow->createParticipantGuest($webShowDyte, $this->Member);
        $token = $participantData['token'];
        $link = "<script>window.open(`/liveStream.php?authToken=$token&Entertainer_id=$EntertainerId`)</script>";
        echo $link;
        
        $prev = "<script>window.location.href = '/?mode=PublicLive'</script>";
        echo $prev;
    }
    
    public function activate()
    {
        $filters = array(new Filter(Filter::Match, array('email' => StaysailIO::get('email'))));
        $member = $this->framework->getSingle('Member', $filters);
        $member->email_verified = 1;
        $member->save();

        header("Location: /");
    }
}