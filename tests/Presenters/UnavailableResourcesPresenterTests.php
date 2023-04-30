<?php
/**
 * Copyright 2017-2020 Nick Korbel
 *
 * This file is part of Booked Scheduler.
 *
 * Booked Scheduler is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Booked Scheduler is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Booked Scheduler.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'Pages/Ajax/UnavailableResourcesPage.php');
require_once(ROOT_DIR . 'Presenters/UnavailableResourcesPresenter.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/ResourceAvailability.php');

class UnavailableResourcesPresenterTests extends TestBase
{
    /**
     * @var FakeReservationConflictIdentifier
     */
    private $reservationConflictIdentifier;

    /**
     * @var FakeAvailableResourcesPage
     */
    private $page;

    /**
     * @var UnavailableResourcesPresenter
     */
    private $presenter;
    /**
     * @var FakeResourceRepository
     */
    private $resourceRepository;
    /**
     * @var FakeReservationRepository
     */
    private $reservationRepository;

    public function setUp(): void
    {
        parent::setup();

        $this->reservationConflictIdentifier = new FakeReservationConflictIdentifier();
        $this->page = new FakeAvailableResourcesPage($this->fakeUser);
        $this->resourceRepository = new FakeResourceRepository();
        $this->resourceRepository->_ScheduleResourceList = array(
            new FakeBookableResource(1),
            new FakeBookableResource(2),
            new FakeBookableResource(3),
            new FakeBookableResource(4),
            new FakeBookableResource(5),
        );

        $this->reservationRepository = new FakeReservationRepository();

        $this->presenter = new UnavailableResourcesPresenter($this->page, $this->reservationConflictIdentifier, $this->fakeUser, $this->resourceRepository, $this->reservationRepository);
    }

    public function testGetsUnavailableResourceIdsWhenNotTheSameReservation()
    {
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult(false);
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult(false);

        $builder = new ExistingReservationSeriesBuilder();
        $series = $builder->Build();
        $this->reservationRepository->_Series = $series;

        $this->page->_ReferenceNumber = "123";
        $this->presenter->PageLoad();

        $bound = $this->page->_BoundAvailability;

        $this->assertEquals(array(3, 5), $bound);
        $this->assertEquals($this->page->GetDuration()->ToUtc(), $series->CurrentInstance()->Duration()->ToUtc());
    }

    public function testGetsUnavailableForNewReservation()
    {
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult(false);
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult();
        $this->reservationConflictIdentifier->_IndexedConflicts[] = new FakeReservationConflictResult(false);

        $this->page->_ReferenceNumber = "";
        $this->presenter->PageLoad();

        $bound = $this->page->_BoundAvailability;

        $this->assertEquals(array(3, 5), $bound);
        $this->assertEquals($this->resourceRepository->_ScheduleResourceList[0], $this->reservationConflictIdentifier->_Series[0]->Resource());
    }
}

class FakeAvailableResourcesPage implements IAvailableResourcesPage
{
    public $_StartDate;
    public $_EndDate;
    public $_StartTime;
    public $_EndTime;
    public $_ReferenceNumber;
    public $_BoundAvailability;
    public $_User;
    public $_ScheduleId;

    public function __construct(UserSession $user)
    {
        $this->_StartDate = '2016-11-23';
        $this->_EndDate = '2016-11-24';
        $this->_StartTime = '08:30';
        $this->_EndTime = '17:30';
        $this->_User = $user;
        $this->_ScheduleId = 1;
    }

    public function GetDuration()
    {
        return DateRange::Create($this->_StartDate . ' ' . $this->_StartTime, $this->_EndDate . ' ' . $this->_EndTime, $this->_User->Timezone);
    }

    public function GetStartDate()
    {
        return $this->_StartDate;
    }

    public function GetEndDate()
    {
        return $this->_EndDate;
    }

    public function GetReferenceNumber()
    {
        return $this->_ReferenceNumber;
    }

    public function GetStartTime()
    {
        return $this->_StartTime;
    }

    public function GetEndTime()
    {
        return $this->_EndTime;
    }

    public function BindUnavailable($unavailableResourceIds)
    {
        $this->_BoundAvailability = $unavailableResourceIds;
    }

    public function GetScheduleId()
    {
        return $this->_ScheduleId;
    }
}
