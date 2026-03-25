<?php

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Data\Attendance\LocationValidationResultData;
use App\Exceptions\Attendance\InvalidAttendanceLocationException;
use App\Exceptions\Attendance\LocationOutOfRangeException;
use App\Exceptions\Attendance\LowLocationAccuracyException;
use App\Exceptions\Attendance\OfficeLocationCoordinateMissingException;

class AttendanceLocationValidationService
{
    /**
     * Kalau jarak hanya sedikit di luar radius, tandai suspicious dulu.
     * Ini sengaja pragmatis untuk noise GPS.
     */
    private const DEFAULT_SUSPICIOUS_RADIUS_MARGIN_METER = 25.0;

    /**
     * Accuracy > threshold policy tapi <= nilai ini masih dianggap suspicious,
     * bukan langsung invalid.
     */
    private const DEFAULT_MAX_SUSPICIOUS_ACCURACY_METER = 100.0;

    public function __construct() {}

    /**
     * Validate lokasi absensi terhadap policy kantor.
     *
     * Hasil:
     * - valid     => return result valid
     * - suspicious=> return result suspicious
     * - invalid   => lempar exception
     */
    public function validateForPolicy(
        AttendancePolicyData $policy,
        float $latitude,
        float $longitude,
        ?float $accuracyMeter = null
    ): LocationValidationResultData {
        $this->assertCoordinatesAreValid($latitude, $longitude);
        $this->assertOfficeCoordinatesAreAvailable($policy);
        $this->assertPolicyThresholdsAreValid($policy);
        $this->assertAccuracyValueIsValid($accuracyMeter);

        $distanceMeter = $this->distanceInMeters(
            $latitude,
            $longitude,
            (float) $policy->officeLatitude,
            (float) $policy->officeLongitude
        );

        $accuracyEvaluation = $this->evaluateAccuracy($accuracyMeter, $policy);
        $radiusEvaluation = $this->evaluateRadius($distanceMeter, $policy);

        // Hard invalid dari accuracy
        if ($accuracyEvaluation['status'] === 'INVALID') {
            throw new LowLocationAccuracyException(
                message: 'Akurasi GPS terlalu rendah untuk absensi.',
                context: [
                    'accuracy_meter' => $accuracyMeter,
                    'max_allowed_accuracy_meter' => (float) $policy->minLocationAccuracyMeter,
                    'distance_meter' => $distanceMeter,
                    'allowed_radius_meter' => (float) $policy->allowedRadiusMeter,
                ]
            );
        }

        // Hard invalid dari radius
        if ($radiusEvaluation['status'] === 'INVALID') {
            throw new LocationOutOfRangeException(
                message: 'Lokasi Anda berada di luar radius absensi yang diizinkan.',
                context: [
                    'distance_meter' => $distanceMeter,
                    'allowed_radius_meter' => (float) $policy->allowedRadiusMeter,
                    'accuracy_meter' => $accuracyMeter,
                ]
            );
        }

        $reasons = [];
        if ($accuracyEvaluation['reason'] !== null) {
            $reasons[] = $accuracyEvaluation['reason'];
        }
        if ($radiusEvaluation['reason'] !== null) {
            $reasons[] = $radiusEvaluation['reason'];
        }

        $isSuspicious = $accuracyEvaluation['status'] === 'SUSPICIOUS'
            || $radiusEvaluation['status'] === 'SUSPICIOUS';

        if ($isSuspicious) {
            return LocationValidationResultData::suspicious(
                distanceMeter: $distanceMeter,
                accuracyMeter: $accuracyMeter ?? 0.0,
                reason: implode('|', $reasons)
            );
        }

        return LocationValidationResultData::valid(
            distanceMeter: $distanceMeter,
            accuracyMeter: $accuracyMeter ?? 0.0,
            reason: null
        );
    }

    /**
     * Haversine formula.
     */
    public function distanceInMeters(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $earthRadius = 6371000.0;

        $fromLatRad = deg2rad($fromLatitude);
        $fromLngRad = deg2rad($fromLongitude);
        $toLatRad = deg2rad($toLatitude);
        $toLngRad = deg2rad($toLongitude);

        $latDelta = $toLatRad - $fromLatRad;
        $lngDelta = $toLngRad - $fromLngRad;

        $a = sin($latDelta / 2) ** 2
            + cos($fromLatRad) * cos($toLatRad) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    private function assertCoordinatesAreValid(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidAttendanceLocationException(
                message: 'Latitude tidak valid.',
                context: [
                    'latitude' => $latitude,
                ]
            );
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidAttendanceLocationException(
                message: 'Longitude tidak valid.',
                context: [
                    'longitude' => $longitude,
                ]
            );
        }

        // 0,0 technically valid secara geografi, tapi untuk attendance kantor ini nyaris pasti data sampah.
        if ($latitude == 0.0 && $longitude == 0.0) {
            throw new InvalidAttendanceLocationException(
                message: 'Koordinat lokasi tidak valid.',
                context: [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );
        }
    }

    private function assertOfficeCoordinatesAreAvailable(AttendancePolicyData $policy): void
    {
        if (! isset($policy->officeLatitude, $policy->officeLongitude)) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Koordinat kantor belum dikonfigurasi.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                ]
            );
        }

        if ($policy->officeLatitude < -90 || $policy->officeLatitude > 90) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Latitude kantor tidak valid.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'office_latitude' => $policy->officeLatitude,
                ]
            );
        }

        if ($policy->officeLongitude < -180 || $policy->officeLongitude > 180) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Longitude kantor tidak valid.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'office_longitude' => $policy->officeLongitude,
                ]
            );
        }

        if ((float) $policy->officeLatitude === 0.0 && (float) $policy->officeLongitude === 0.0) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Koordinat kantor belum dikonfigurasi dengan benar.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'office_latitude' => $policy->officeLatitude,
                    'office_longitude' => $policy->officeLongitude,
                ]
            );
        }
    }

    private function assertPolicyThresholdsAreValid(AttendancePolicyData $policy): void
    {
        if ((float) $policy->allowedRadiusMeter <= 0) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Radius kantor belum dikonfigurasi dengan benar.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'allowed_radius_meter' => $policy->allowedRadiusMeter,
                ]
            );
        }

        if ((float) $policy->minLocationAccuracyMeter <= 0) {
            throw new OfficeLocationCoordinateMissingException(
                message: 'Batas akurasi lokasi belum dikonfigurasi dengan benar.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'min_location_accuracy_meter' => $policy->minLocationAccuracyMeter,
                ]
            );
        }
    }

    private function assertAccuracyValueIsValid(?float $accuracyMeter): void
    {
        if ($accuracyMeter !== null && $accuracyMeter < 0) {
            throw new InvalidAttendanceLocationException(
                message: 'Nilai akurasi lokasi tidak valid.',
                context: [
                    'accuracy_meter' => $accuracyMeter,
                ]
            );
        }
    }

    /**
     * Output:
     * [
     *   'status' => 'VALID' | 'SUSPICIOUS' | 'INVALID',
     *   'reason' => ?string,
     * ]
     */
    private function evaluateAccuracy(
        ?float $accuracyMeter,
        AttendancePolicyData $policy
    ): array {
        if ($accuracyMeter === null) {
            return [
                'status' => 'SUSPICIOUS',
                'reason' => 'MISSING_ACCURACY',
            ];
        }

        $maxGoodAccuracy = (float) $policy->minLocationAccuracyMeter;

        if ($accuracyMeter <= $maxGoodAccuracy) {
            return [
                'status' => 'VALID',
                'reason' => null,
            ];
        }

        if ($accuracyMeter <= self::DEFAULT_MAX_SUSPICIOUS_ACCURACY_METER) {
            return [
                'status' => 'SUSPICIOUS',
                'reason' => 'LOW_ACCURACY',
            ];
        }

        return [
            'status' => 'INVALID',
            'reason' => 'ACCURACY_TOO_LOW',
        ];
    }

    /**
     * Output:
     * [
     *   'status' => 'VALID' | 'SUSPICIOUS' | 'INVALID',
     *   'reason' => ?string,
     * ]
     */
    private function evaluateRadius(
        float $distanceMeter,
        AttendancePolicyData $policy
    ): array {
        $allowedRadius = (float) $policy->allowedRadiusMeter;
        $suspiciousBoundary = $allowedRadius + self::DEFAULT_SUSPICIOUS_RADIUS_MARGIN_METER;

        if ($distanceMeter <= $allowedRadius) {
            return [
                'status' => 'VALID',
                'reason' => null,
            ];
        }

        if ($distanceMeter <= $suspiciousBoundary) {
            return [
                'status' => 'SUSPICIOUS',
                'reason' => 'OUTSIDE_RADIUS_NEAR_BOUNDARY',
            ];
        }

        return [
            'status' => 'INVALID',
            'reason' => 'OUT_OF_RADIUS',
        ];
    }
}
