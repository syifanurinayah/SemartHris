<?php

namespace KejawenLab\Application\SemartHris\Twig;

use KejawenLab\Application\SemartHris\Component\Attendance\Model\AttendanceInterface;
use KejawenLab\Application\SemartHris\Component\Employee\Repository\EmployeeRepositoryInterface;
use KejawenLab\Application\SemartHris\Component\Reason\Repository\ReasonRepositoryInterface;
use KejawenLab\Application\SemartHris\Util\SettingUtil;
use KejawenLab\Application\SemartHris\Util\StringUtil;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@kejawenlab.com>
 */
class AttendanceExtension extends \Twig_Extension
{
    /**
     * @var EmployeeRepositoryInterface
     */
    private $employeeRepository;

    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;

    /**
     * @var string
     */
    private $attendanceClass;

    /**
     * @param EmployeeRepositoryInterface $employeeRepository
     * @param ReasonRepositoryInterface   $reasonRepository
     * @param string                      $attendanceClass
     */
    public function __construct(EmployeeRepositoryInterface $employeeRepository, ReasonRepositoryInterface $reasonRepository, string $attendanceClass)
    {
        $this->employeeRepository = $employeeRepository;
        $this->reasonRepository = $reasonRepository;
        $this->attendanceClass = $attendanceClass;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('semarthris_create_attendance_preview', [$this, 'createAttendancePreview']),
        ];
    }

    /**
     * @param array $preview
     *
     * @return AttendanceInterface
     */
    public function createAttendancePreview(array $preview): AttendanceInterface
    {
        if (!(isset($preview['employee_code']) || isset($preview['date']))) {
            throw new \InvalidArgumentException();
        }

        /* @var AttendanceInterface $attendance */
        if (!$employee = $this->employeeRepository->findByCode(StringUtil::sanitize($preview['employee_code']))) {
            throw new \InvalidArgumentException();
        }

        $attendanceDate = \DateTime::createFromFormat(SettingUtil::get(SettingUtil::DATE_FORMAT), StringUtil::sanitize($preview['date']));
        $attendance = new $this->attendanceClass();
        $attendance->setAttendanceDate($attendanceDate);
        $attendance->setEmployee($employee);

        if (!(isset($preview['check_in']) && $preview['check_in']) || !(isset($preview['check_out']) && $preview['check_out'])) {
            $attendance->setAbsent(true);
            if (isset($preview['reason_code']) && $reason = $this->reasonRepository->findAbsentReasonByCode($preview['reason_code'])) {
                $attendance->setReason($reason);
            }
        } else {
            $attendance->setCheckIn(\DateTime::createFromFormat('H:i', StringUtil::sanitize($preview['check_in'])));
            $attendance->setCheckOut(\DateTime::createFromFormat('H:i', StringUtil::sanitize($preview['check_out'])));
        }

        return $attendance;
    }
}
