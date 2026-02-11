"""
Lunar calendar conversion based on Hồ Ngọc Đức's algorithm.
Adapted for Vietnamese lunar calendar (UTC+7).
Reference: https://www.informatik.uni-leipzig.de/~duc/amlich/
"""

import math
from datetime import date, timedelta

PI = math.pi
TIMEZONE_OFFSET = 7.0 / 24  # UTC+7 for Vietnam


def _jd_from_date(dd, mm, yy):
    a = (14 - mm) // 12
    y = yy + 4800 - a
    m = mm + 12 * a - 3
    jd = dd + (153 * m + 2) // 5 + 365 * y + y // 4 - y // 100 + y // 400 - 32045
    if jd < 2299161:
        jd = dd + (153 * m + 2) // 5 + 365 * y + y // 4 - 32083
    return jd


def _jd_to_date(jd):
    if jd > 2299160:
        a = jd + 32044
        b = (4 * a + 3) // 146097
        c = a - (146097 * b) // 4
    else:
        b = 0
        c = jd + 32082
    d = (4 * c + 3) // 1461
    e = c - (1461 * d) // 4
    m = (5 * e + 2) // 153
    day = e - (153 * m + 2) // 5 + 1
    month = m + 3 - 12 * (m // 10)
    year = b * 100 + d - 4800 + m // 10
    return day, month, year


def _new_moon(k):
    T = k / 1236.85
    T2 = T * T
    T3 = T2 * T
    dr = PI / 180
    Jd1 = 2415020.75933 + 29.53058868 * k + 0.0001178 * T2 - 0.000000155 * T3
    Jd1 = Jd1 + 0.00033 * math.sin((166.56 + 132.87 * T - 0.009173 * T2) * dr)
    M = 359.2242 + 29.10535608 * k - 0.0000333 * T2 - 0.00000347 * T3
    Mpr = 306.0253 + 385.81691806 * k + 0.0107306 * T2 + 0.00001236 * T3
    F = 21.2964 + 390.67050646 * k - 0.0016528 * T2 - 0.00000239 * T3
    C1 = (0.1734 - 0.000393 * T) * math.sin(M * dr) + 0.0021 * math.sin(2 * dr * M)
    C1 = C1 - 0.4068 * math.sin(Mpr * dr) + 0.0161 * math.sin(dr * 2 * Mpr)
    C1 = C1 - 0.0004 * math.sin(dr * 3 * Mpr)
    C1 = C1 + 0.0104 * math.sin(dr * 2 * F) - 0.0051 * math.sin(dr * (M + Mpr))
    C1 = C1 - 0.0074 * math.sin(dr * (M - Mpr)) + 0.0004 * math.sin(dr * (2 * F + M))
    C1 = C1 - 0.0004 * math.sin(dr * (2 * F - M)) - 0.0006 * math.sin(dr * (2 * F + Mpr))
    C1 = C1 + 0.0010 * math.sin(dr * (2 * F - Mpr)) + 0.0005 * math.sin(dr * (2 * Mpr + M))
    if T < -11:
        deltat = 0.001 + 0.000839 * T + 0.0002261 * T2 - 0.00000845 * T3 - 0.000000081 * T * T3
    else:
        deltat = -0.000278 + 0.000265 * T + 0.000262 * T2
    JdNew = Jd1 + C1 - deltat
    return JdNew


def _sun_longitude(jdn):
    T = (jdn - 2451545.0) / 36525
    T2 = T * T
    dr = PI / 180
    M = 357.52910 + 35999.05030 * T - 0.0001559 * T2 - 0.00000048 * T * T2
    L0 = 280.46645 + 36000.76983 * T + 0.0003032 * T2
    DL = (1.914600 - 0.004817 * T - 0.000014 * T2) * math.sin(dr * M)
    DL = DL + (0.019993 - 0.000101 * T) * math.sin(dr * 2 * M) + 0.000290 * math.sin(dr * 3 * M)
    L = L0 + DL
    L = L * dr
    L = L - PI * 2 * (int(L / (PI * 2)))
    return L


def _get_sun_longitude(dayNumber):
    return int(_sun_longitude(_new_moon(dayNumber) + 0.5 + TIMEZONE_OFFSET) / PI * 6)


def _get_lunar_month_11(yy):
    off = _jd_from_date(31, 12, yy) - 2415021
    k = int(off / 29.530588853)
    nm = _new_moon(k)
    sunLong = _get_sun_longitude(k)
    if sunLong >= 9:
        nm = _new_moon(k - 1)
    return int(nm + 0.5 + TIMEZONE_OFFSET)


def _get_leap_month_offset(a11):
    k = int((a11 - 2415021.076998695) / 29.530588853 + 0.5)
    last = 0
    i = 1
    arc = _get_sun_longitude(k + i)
    while True:
        last = arc
        i += 1
        arc = _get_sun_longitude(k + i)
        if arc != last or i >= 14:
            break
    return i - 1


def solar_to_lunar(dd, mm, yy):
    """Convert solar date to lunar date. Returns (day, month, year, is_leap)."""
    dayNumber = _jd_from_date(dd, mm, yy)
    k = int((dayNumber - 2415021.076998695) / 29.530588853)
    monthStart = _new_moon(k + 1)
    if int(monthStart + 0.5 + TIMEZONE_OFFSET) > dayNumber:
        monthStart = _new_moon(k)
    a11 = _get_lunar_month_11(yy)
    b11 = a11
    if a11 >= int(monthStart + 0.5 + TIMEZONE_OFFSET):
        lunarYear = yy
        a11 = _get_lunar_month_11(yy - 1)
    else:
        lunarYear = yy + 1
        b11 = _get_lunar_month_11(yy + 1)

    lunarDay = dayNumber - int(monthStart + 0.5 + TIMEZONE_OFFSET) + 1
    diff = int((int(monthStart + 0.5 + TIMEZONE_OFFSET) - a11) / 29)
    lunarLeap = False
    lunarMonth = diff + 11

    if b11 - a11 > 365:
        leapMonthDiff = _get_leap_month_offset(a11)
        if diff >= leapMonthDiff:
            lunarMonth = diff + 10
            if diff == leapMonthDiff:
                lunarLeap = True

    if lunarMonth > 12:
        lunarMonth = lunarMonth - 12
    if lunarMonth >= 11 and diff < 4:
        lunarYear -= 1

    return lunarDay, lunarMonth, lunarYear, lunarLeap


def lunar_to_solar(lunarDay, lunarMonth, lunarYear, lunarLeap=False):
    """Convert lunar date to solar date. Returns (day, month, year) or None if invalid."""
    if lunarMonth < 11:
        a11 = _get_lunar_month_11(lunarYear - 1)
        b11 = _get_lunar_month_11(lunarYear)
    else:
        a11 = _get_lunar_month_11(lunarYear)
        b11 = _get_lunar_month_11(lunarYear + 1)

    k = int(0.5 + (a11 - 2415021.076998695) / 29.530588853)
    off = lunarMonth - 11
    if off < 0:
        off += 12

    if b11 - a11 > 365:
        leapOff = _get_leap_month_offset(a11)
        leapMonth = leapOff - 2
        if leapMonth < 0:
            leapMonth += 12
        if lunarLeap and lunarMonth != leapMonth:
            return None
        elif lunarLeap or (off >= leapOff):
            off += 1

    monthStart = _new_moon(k + off)
    jd = int(monthStart + 0.5 + TIMEZONE_OFFSET) + lunarDay - 1
    return _jd_to_date(jd)


def get_solar_date_for_anniversary(lunar_day, lunar_month, solar_year):
    """
    Given a lunar day/month and a target solar year, find the solar date.
    Tries the given solar year and the next year to find the correct match.
    Returns (day, month, year) tuple or None.
    """
    for year in [solar_year, solar_year - 1, solar_year + 1]:
        result = lunar_to_solar(lunar_day, lunar_month, year, False)
        if result:
            d, m, y = result
            if y == solar_year:
                return result
    # Fallback: return the result for the given solar year
    return lunar_to_solar(lunar_day, lunar_month, solar_year, False)
