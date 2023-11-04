<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Console\Command;

class RegisterHoliday extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register:holiday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'insert data into holidays table';

    private $calendarId = 'japanese__ja@holiday.calendar.google.com';
    private $urlFormat = 'https://calendar.google.com/calendar/ical/%s/public/full.ics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        list($result, $response) = $this->getCalendar();
        if ($result) {
            $holidays = $this->getHolidayData($response->getBody()->getContents());
            if (!empty($holidays)) {
                $saveData = [];
                foreach ($holidays as $holiday) {
                    if (is_null(Holiday::where('date', $holiday['date'])->first())) {
                        $saveData[] = $holiday;
                    }
                }
                if (!empty($saveData)) {
                    \DB::table('holidays')->insert($saveData);
                }
            }
        }

        $this->end();

        return $result;
    }

    /**
     * @return array
     */
    private function getCalendar()
    {
        $result = true;
        $response = null;

        $url = sprintf($this->urlFormat, urlencode($this->calendarId));

        try {
            $client = new Client();
            $response = $client->request('GET', $url, ['verify' => false]);
        } catch (BadResponseException $e) {
            $code = $e->getResponse()->getStatusCode();
            $reason = $e->getResponse()->getReasonPhrase();
            $message = sprintf('failed to get calendar data. code:%s, reason:%s', $code, $reason);
            $this->error($this->logPrefix().$message);
            $result = false;
        } catch (\Throwable $e) {
            $this->error($this->logPrefix().$e->getMessage()."\n".$e->getTraceAsString());
            $result = false;
        }

        return [$result, $response];
    }

    /**
     * @param $calendarText
     *
     * @return array
     */
    private function getHolidayData($calendarText)
    {
        $holidays = [];
        // 少し乱暴ではある
        $events = explode('BEGIN:VEVENT', $calendarText);
        foreach ($events as $event) {
            $entries = explode("\n", $event);
            $data = [];
            foreach ($entries as $entry) {
                $kv = explode(':', $entry);
                $data[$kv[0]] = isset($kv[1]) ? $kv[1] : '';
            }
            if (!empty($data['DTSTART;VALUE=DATE'])) {
                $holidays[] = [
                    'date' => date('Y-m-d', strtotime($data['DTSTART;VALUE=DATE'])),
                    'name' => $data['SUMMARY'],
                ];
            }
        }
        if (!empty($holidays)) {
            $holidays = array_values($this->array_sort($holidays, 'date'));
        }

        return $holidays;
    }

    public function array_sort($array, $on, $order = SORT_ASC)
    {
        $new_array = [];
        $sortable_array = [];

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

            foreach ($sortable_array as $k => $v) {
                array_push($new_array, $array[$k]);
            }
        }

        return $new_array;
    }
}
