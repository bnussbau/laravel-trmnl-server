<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'proxy_cloud' => 'boolean',
        'last_log_request' => 'json',
    ];

    public function getBatteryPercentAttribute()
    {
        $volts = $this->last_battery_voltage;

        // Define min and max voltage for Li-ion battery (3.0V empty, 4.2V full)
        $min_volt = 3.0;
        $max_volt = 4.2;

        // Ensure the voltage is within range
        if ($volts <= $min_volt) {
            return 0;
        } elseif ($volts >= $max_volt) {
            return 100;
        }

        // Calculate percentage
        $percent = (($volts - $min_volt) / ($max_volt - $min_volt)) * 100;

        return round($percent);
    }

    public function getWifiStrenghAttribute()
    {
        $rssi = $this->last_rssi_level;
        if ($rssi >= 0) {
            return 0; // No signal (0 bars)
        } elseif ($rssi <= -80) {
            return 1; // Weak signal (1 bar)
        } elseif ($rssi <= -60) {
            return 2; // Moderate signal (2 bars)
        } else {
            return 3; // Strong signal (3 bars)
        }
    }
}
