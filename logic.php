<?

/*

	        @Date		2015-01-23
	        @Author		L.J.
	        @Param		$heart  the value of using hearts
						$this->R  Redis Object
						$this->UserKey  user id
						$this->heart    the value of hearts
						$this->expires  complete time
						$this->hour     recharge an heart for hour / 3600s

*/

class  logic
{
    private $R; //Redis Object

    public function Charge($heart = -10,$useEnergy)
    {
        $Unit = 10;
        $Max = 100;

        if ($this->R->exists($this->UserKey)) {// if there is UserKey it will calculate expire time.

            /*

                1. $heart !=0  if it it grater 0 ,it will recharge  otherwise will consume it.
                2. $this->heart+$heart smaller than $Max .
                3. $this->heart+$useEnergy grater than  0.

            */
            $CheckHeart = $this->heart + $useEnergy;
            if (abs($heart) > 0 and $CheckHeart < $Max and $CheckHeart >= 0) {
                //하트 사용 및 충전
                $this->heart = $this->R->INCRBY($this->UserKey, $heart);//레디스 명령 참고 하세요.

                $hour = round(($Max - $this->heart) / $Unit, 1);// calculate left time to fully recharge.

                $this->R->expire($this->UserKey, $this->hour * $hour);//set up expire time.

            } else if ($CheckHeart >= $Max) {

                $this->R->set($this->UserKey, $Max);
                $this->heart = $Max;


            } else {
                //calculate heart by time
                $TTL = $this->R->TTL($this->EnergyKey); //left time

                if ($TTL >= $this->hour) {//pass an hour

                    $HourEnergy = round($TTL / $this->hour) * $Unit + round($TTL % $this->hour / $this->hour, 1) * $Unit; //round  and Mod

                } else {//not full of an hour

                    $HourEnergy = round($TTL % $this->hour / $this->hour, 1) * $Unit;
                }

                $Incr = $Max - $HourEnergy + $this->heart;//increase heart
                if ($Incr > 0) $this->heart = $this->R->INCRBY($this->EnergyKey, $Incr);

            }
        } else { // if there is not a key, it will fully charged.

            $this->R->set($this->UserKey, $Max);
            $this->heart = $Max;
        }


        $this->expires = $this->R->TTL($this->UserKey); //expire time(s) or -1

    }
}