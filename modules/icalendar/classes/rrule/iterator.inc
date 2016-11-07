<?php 
/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2016 Market Acumen, Inc.
 *
 * http://www.ietf.org/rfc/rfc2445.txt
 *
 * Based on python-dateutil, just like everyone else's version
 *
 * https://pypi.python.org/pypi/python-dateutil/2.5.3
 * 
 * see module's README.txt for license information.
 */
namespace RRule;


use zesk\Date;

class Iterator {
    
	/**
	 * @var Rule
	 */
	private $rrule = null;
	
	
	private $lastyear = null;
	
	private $lastmonth = null;
                 
	private $yearlen = null;
	private $nextyearlen = null;
	private $yearordinal = null;
	private $yearweekday = null;
	
	private $mmask = null;
	private $mrange = null;
	private $mdaymask = null;
	private $nmdaymask = null;
	private $wdaymask = null;
	private $wnomask = null;
	private $nwdaymask = null;

	public function __construct(Rule $rrule) {
		$this->rrule = $rrule;
	}

    public function rebuild($year, $month) {
    	$date = Date::instance($year, $month, 1);
    	$leap_index = $date->is_leap_year() ? 1 : 0;
    	$next_leap_index = $date->is_leap_year($year + 1) ? 1 : 0;
        # Every mask is 7 days longer to handle cross-year weekly periods.
        $rr = $this->rrule;
        if ($year !== $this->lastyear) {
           $this->yearlen = 365 + $leap_index;
           $this->nextyearlen = 365 + $next_leap_index;
           $firstyday = Date::instance($year, 1, 1);
           $this->yearordinal = $firstyday->gregorian();
           $wday = $this->yearweekday = $firstyday->weekday();

           $this->mmask = Rule::$MMASK[$leap_index];
           $this->mdaymask = Rule::$MDAYMASK[$leap_index]
           $this->nmdaymask = Rule::$NMDAYMASK[$leap_index];
           $this->wdaymask = array_slice(Rule::$WDAYMASK, $wday);
           $this->mrange = Rule::$MRANGE[$leap_index];
            
            if not rr._byweekno:
                $this->wnomask = None
            else:
                $this->wnomask = [0]*($this->yearlen+7)
                # no1wkst = firstwkst = $this->wdaymask.index(rr._wkst)
                no1wkst = firstwkst = (7-$this->yearweekday+rr._wkst) % 7
                if no1wkst >= 4:
                    no1wkst = 0
                    # Number of days in the year, plus the days we got
                    # from last year.
                    wyearlen = $this->yearlen+($this->yearweekday-rr._wkst) % 7
                else:
                    # Number of days in the year, minus the days we
                    # left in last year.
                    wyearlen = $this->yearlen-no1wkst
                div, mod = divmod(wyearlen, 7)
                numweeks = div+mod//4
                for n in rr._byweekno:
                    if n < 0:
                        n += numweeks+1
                    if not (0 < n <= numweeks):
                        continue
                    if n > 1:
                        i = no1wkst+(n-1)*7
                        if no1wkst != firstwkst:
                            i -= 7-firstwkst
                    else:
                        i = no1wkst
                    for j in range(7):
                        $this->wnomask[i] = 1
                        i += 1
                        if $this->wdaymask[i] == rr._wkst:
                            break
                if 1 in rr._byweekno:
                    # Check week number 1 of next year as well
                    # TODO: Check -numweeks for next year.
                    i = no1wkst+numweeks*7
                    if no1wkst != firstwkst:
                        i -= 7-firstwkst
                    if i < $this->yearlen:
                        # If week starts in next year, we
                        # don't care about it.
                        for j in range(7):
                            $this->wnomask[i] = 1
                            i += 1
                            if $this->wdaymask[i] == rr._wkst:
                                break
                if no1wkst:
                    # Check last week number of last year as
                    # well. If no1wkst is 0, either the year
                    # started on week start, or week number 1
                    # got days from last year, so there are no
                    # days from last year's last week number in
                    # this year.
                    if -1 not in rr._byweekno:
                        lyearweekday = datetime.date(year-1, 1, 1).weekday()
                        lno1wkst = (7-lyearweekday+rr._wkst) % 7
                        lyearlen = 365+calendar.isleap(year-1)
                        if lno1wkst >= 4:
                            lno1wkst = 0
                            lnumweeks = 52+(lyearlen +
                                            (lyearweekday-rr._wkst) % 7) % 7//4
                        else:
                            lnumweeks = 52+($this->yearlen-no1wkst) % 7//4
                    else:
                        lnumweeks = -1
                    if lnumweeks in rr._byweekno:
                        for i in range(no1wkst):
                            $this->wnomask[i] = 1

        if (rr._bynweekday and (month != $this->lastmonth or
                                year != $this->lastyear)):
            ranges = []
            if rr._freq == YEARLY:
                if rr._bymonth:
                    for month in rr._bymonth:
                        ranges.append($this->mrange[month-1:month+1])
                else:
                    ranges = [(0, $this->yearlen)]
            elif rr._freq == MONTHLY:
                ranges = [$this->mrange[month-1:month+1]]
            if ranges:
                # Weekly frequency won't get here, so we may not
                # care about cross-year weekly periods.
                $this->nwdaymask = [0]*$this->yearlen
                for first, last in ranges:
                    last -= 1
                    for wday, n in rr._bynweekday:
                        if n < 0:
                            i = last+(n+1)*7
                            i -= ($this->wdaymask[i]-wday) % 7
                        else:
                            i = first+(n-1)*7
                            i += (7-$this->wdaymask[i]+wday) % 7
                        if first <= i <= last:
                            $this->nwdaymask[i] = 1

        if rr._byeaster:
            $this->eastermask = [0]*($this->yearlen+7)
            eyday = easter.easter(year).toordinal()-$this->yearordinal
            for offset in rr._byeaster:
                $this->eastermask[eyday+offset] = 1

        $this->lastyear = year
        $this->lastmonth = month

    def ydayset(self, year, month, day):
        return list(range($this->yearlen)), 0, $this->yearlen

    def mdayset(self, year, month, day):
        dset = [None]*$this->yearlen
        start, end = $this->mrange[month-1:month+1]
        for i in range(start, end):
            dset[i] = i
        return dset, start, end

    def wdayset(self, year, month, day):
        # We need to handle cross-year weeks here.
        dset = [None]*($this->yearlen+7)
        i = datetime.date(year, month, day).toordinal()-$this->yearordinal
        start = i
        for j in range(7):
            dset[i] = i
            i += 1
            # if (not (0 <= i < $this->yearlen) or
            #    $this->wdaymask[i] == $this->rrule._wkst):
            # This will cross the year boundary, if necessary.
            if $this->wdaymask[i] == $this->rrule._wkst:
                break
        return dset, start, i

    def ddayset(self, year, month, day):
        dset = [None] * $this->yearlen
        i = datetime.date(year, month, day).toordinal() - $this->yearordinal
        dset[i] = i
        return dset, i, i + 1

    def htimeset(self, hour, minute, second):
        tset = []
        rr = $this->rrule
        for minute in rr._byminute:
            for second in rr._bysecond:
                tset.append(datetime.time(hour, minute, second,
                                          tzinfo=rr._tzinfo))
        tset.sort()
        return tset

    def mtimeset(self, hour, minute, second):
        tset = []
        rr = $this->rrule
        for second in rr._bysecond:
            tset.append(datetime.time(hour, minute, second, tzinfo=rr._tzinfo))
        tset.sort()
        return tset

    def stimeset(self, hour, minute, second):
        return (datetime.time(hour, minute, second,
                tzinfo=$this->rrule._tzinfo),)
