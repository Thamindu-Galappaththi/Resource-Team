@extends('layouts.app')

@section('title', 'Resource Owner Calendar')

@section('content')
<main class="resource-calendar-page">
    <div class="resource-calendar-hero">
        <div>
            <p class="resource-calendar-eyebrow"><i class="ti ti-calendar-event"></i> Resource management</p>
            <h1>Resource Owner Calendar</h1>
            <p class="resource-calendar-intro">Monitor and manage resource reservations from one central schedule.</p>
        </div>
        <div class="resource-calendar-actions">
            <button type="button" class="calendar-icon-button" aria-label="Notifications" title="Notifications"><i class="ti ti-bell"></i></button>
            <button type="button" class="calendar-icon-button" aria-label="Calendar settings" title="Calendar settings"><i class="ti ti-settings"></i></button>
        </div>
    </div>

    <section class="resource-calendar-shell" aria-label="Resource calendar">
        <div class="resource-calendar-toolbar">
            <div class="resource-calendar-title">
                <span class="resource-calendar-mark"><i class="ti ti-calendar"></i></span>
                <div><p>Booking schedule</p><h2>All resources</h2></div>
            </div>
            <div class="resource-calendar-filters">
                <label class="calendar-select-label" for="statusFilter">Status</label>
                <select id="statusFilter" class="calendar-select" aria-label="Filter by status">
                    <option value="all">All bookings</option><option value="approved">Approved</option><option value="pending">Pending</option>
                </select>
                <div class="calendar-view-switcher" aria-label="Calendar view">
                    <button type="button" class="calendar-view-button active" data-calendar-view="dayGridMonth">Month</button>
                    <button type="button" class="calendar-view-button" data-calendar-view="timeGridWeek">Week</button>
                    <button type="button" class="calendar-view-button" data-calendar-view="timeGridDay">Day</button>
                </div>
            </div>
        </div>
        <div id="resourceCalendar"></div>
    </section>
</main>

<div id="reservationModal" class="resource-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
    <div class="resource-modal-overlay"></div>
    <div class="resource-modal-box">
        <button type="button" id="closeReservationModal" class="resource-modal-close" aria-label="Close reservation details"><i class="ti ti-x"></i></button>
        <p class="resource-modal-eyebrow"><i class="ti ti-calendar-event"></i> Reservation details</p>
        <h2 id="reservationModalTitle">Booking information</h2>
        <div class="resource-detail-list">
            <div><span>Resource</span><strong id="detailResource">-</strong></div><div><span>Date</span><strong id="detailDate">-</strong></div>
            <div><span>Booking time</span><strong id="detailTime">-</strong></div><div><span>Reservation owner</span><strong id="detailOwner">-</strong></div>
            <div><span>Location</span><strong id="detailLocation">-</strong></div><div><span>Status</span><strong id="detailStatus">-</strong></div>
        </div>
    </div>
</div>

<style>
.resource-calendar-page{min-height:calc(100vh - 80px);padding:34px clamp(18px,4vw,56px) 54px;color:#172033;background:#f5f7fb}
.resource-calendar-hero{display:flex;justify-content:space-between;align-items:flex-start;max-width:1320px;margin:0 auto 22px}
.resource-calendar-eyebrow,.resource-modal-eyebrow{margin:0 0 9px;color:#2868b2;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.resource-calendar-eyebrow i,.resource-modal-eyebrow i{margin-right:5px}
.resource-calendar-hero h1{margin:0;color:#172033;font-size:clamp(24px,3vw,34px);font-weight:800;letter-spacing:-.03em}.resource-calendar-intro{margin:8px 0 0;color:#68748a;font-size:14px}.resource-calendar-actions{display:flex;gap:8px}.calendar-icon-button{width:38px;height:38px;border:1px solid #dfe5ef;border-radius:50%;color:#647087;background:#fff;cursor:pointer}.calendar-icon-button:hover{color:#2868b2;border-color:#2868b2}
.resource-calendar-shell{max-width:1320px;margin:0 auto;overflow:hidden;border:1px solid #e3e8f0;border-radius:8px;background:#fff;box-shadow:0 12px 35px rgba(32,54,86,.08)}.resource-calendar-toolbar{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:21px 24px;border-bottom:1px solid #e6eaf1}.resource-calendar-title{display:flex;align-items:center;gap:12px}.resource-calendar-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:8px;color:#fff;background:#2868b2;font-size:21px}.resource-calendar-title p{margin:0 0 3px;color:#8792a5;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.resource-calendar-title h2{margin:0;color:#1d2a42;font-size:18px;font-weight:800}.resource-calendar-filters{display:flex;align-items:center;gap:10px}.calendar-select-label{color:#788397;font-size:12px;font-weight:700}.calendar-select{min-width:130px;height:36px;padding:0 28px 0 10px;border:1px solid #dfe5ef;border-radius:5px;color:#344158;background:#fff;font-size:12px}.calendar-view-switcher{display:flex;padding:3px;border:1px solid #e0e6ef;border-radius:6px;background:#f7f9fc}.calendar-view-button{padding:7px 11px;border:0;border-radius:4px;color:#748097;background:transparent;font-size:12px;cursor:pointer}.calendar-view-button.active{color:#fff;background:#2868b2;box-shadow:0 2px 5px rgba(40,104,178,.2)}#resourceCalendar{padding:14px 20px 22px}
.fc{--fc-border-color:#e8ecf2;--fc-button-bg-color:#fff;--fc-button-border-color:#dfe5ef;--fc-button-text-color:#59667d;--fc-today-bg-color:#f1f6fc;font-size:12px}.fc .fc-toolbar-title{color:#1d2a42;font-size:19px;font-weight:800}.fc .fc-toolbar.fc-header-toolbar{margin:4px 0 18px}.fc .fc-button{padding:7px 11px;border-radius:5px;box-shadow:none;font-size:12px;font-weight:700}.fc .fc-button:hover,.fc .fc-button-primary:not(:disabled).fc-button-active{border-color:#2868b2;color:#fff;background:#2868b2}.fc .fc-daygrid-day-number{padding:10px;color:#657188;font-weight:700;text-decoration:none}.fc .fc-col-header-cell-cushion{padding:10px 4px;color:#7c8799;font-size:10px;font-weight:800;letter-spacing:.09em;text-decoration:none;text-transform:uppercase}.fc .fc-daygrid-day{min-height:112px}.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number{display:inline-grid;place-items:center;width:26px;height:26px;margin:5px;padding:0;border-radius:50%;color:#fff;background:#2868b2}.fc .fc-event{margin:3px 5px;padding:4px 6px;border:0;border-left:3px solid #2868b2;border-radius:3px;color:#315172;background:#e9f2fb;cursor:pointer;font-size:10px;line-height:1.3}.fc .fc-event:hover{background:#dcebf9}
.resource-modal{position:fixed;inset:0;z-index:99999;display:none;place-items:center;padding:20px}.resource-modal.show{display:grid}.resource-modal-overlay{position:absolute;inset:0;background:rgba(18,31,53,.52);backdrop-filter:blur(3px)}.resource-modal-box{position:relative;z-index:1;width:min(450px,100%);padding:28px;border-radius:10px;background:#fff;box-shadow:0 22px 60px rgba(16,30,52,.25)}.resource-modal-close{position:absolute;top:15px;right:15px;width:32px;height:32px;border:0;border-radius:50%;color:#718097;background:#f2f5f9;cursor:pointer}.resource-modal-box h2{margin:0 0 22px;color:#1d2a42;font-size:22px}.resource-detail-list{border-top:1px solid #edf0f4}.resource-detail-list div{display:flex;justify-content:space-between;gap:18px;padding:13px 0;border-bottom:1px solid #edf0f4}.resource-detail-list span{color:#8792a5;font-size:12px}.resource-detail-list strong{color:#273650;font-size:13px;text-align:right}
@media(max-width:760px){.resource-calendar-page{padding:24px 12px 35px}.resource-calendar-toolbar,.resource-calendar-filters{align-items:stretch;flex-direction:column}.resource-calendar-toolbar{padding:17px 15px}.resource-calendar-filters{gap:8px}.calendar-select{width:100%}.calendar-view-switcher{justify-content:stretch}.calendar-view-button{flex:1}#resourceCalendar{padding:8px 4px 14px}.fc .fc-toolbar{flex-wrap:wrap;gap:8px}.fc .fc-toolbar-title{font-size:16px}}
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const element=document.getElementById('resourceCalendar');if(!element)return;
    const events=[
        {id:'1',title:'Meeting Room 2',start:'2026-10-05T09:00:00',end:'2026-10-05T11:00:00',extendedProps:{owner:'John Smith',location:'Main Office',status:'Approved'}},
        {id:'2',title:'Auditorium',start:'2026-10-07T14:00:00',end:'2026-10-07T16:00:00',extendedProps:{owner:'Sarah Fernando',location:'Main Office',status:'Approved'}},
        {id:'3',title:'Meeting Room 5',start:'2026-10-13T10:00:00',end:'2026-10-13T13:00:00',extendedProps:{owner:'Nimal Silva',location:'Conference Wing',status:'Pending'}},
        {id:'4',title:'Lab 2',start:'2026-10-17T09:00:00',end:'2026-10-17T11:30:00',extendedProps:{owner:'Amal Perera',location:'IT Department',status:'Approved'}}
    ];
    const calendar=new FullCalendar.Calendar(element,{initialView:'dayGridMonth',initialDate:'2026-10-01',height:'auto',fixedWeekCount:false,firstDay:0,navLinks:true,editable:false,events:events,headerToolbar:{left:'title',center:'',right:'prev,today,next'},buttonText:{today:'Today'},eventClick:function(info){const details=info.event.extendedProps;document.getElementById('detailResource').textContent=info.event.title;document.getElementById('detailDate').textContent=formatDate(info.event.start);document.getElementById('detailTime').textContent=formatTimeRange(info.event.start,info.event.end);document.getElementById('detailOwner').textContent=details.owner||'-';document.getElementById('detailLocation').textContent=details.location||'-';document.getElementById('detailStatus').textContent=details.status||'-';document.getElementById('reservationModal').classList.add('show');document.getElementById('reservationModal').setAttribute('aria-hidden','false')}});
    calendar.render();
    document.querySelectorAll('[data-calendar-view]').forEach(function(button){button.addEventListener('click',function(){calendar.changeView(this.dataset.calendarView);document.querySelectorAll('[data-calendar-view]').forEach(item=>item.classList.remove('active'));this.classList.add('active')})});
    document.getElementById('statusFilter').addEventListener('change',function(){calendar.removeAllEvents();calendar.addEventSource(this.value==='all'?events:events.filter(event=>event.extendedProps.status.toLowerCase()===this.value))});
    const modal=document.getElementById('reservationModal');function closeModal(){modal.classList.remove('show');modal.setAttribute('aria-hidden','true')}document.getElementById('closeReservationModal').addEventListener('click',closeModal);document.querySelector('.resource-modal-overlay').addEventListener('click',closeModal);document.addEventListener('keydown',event=>{if(event.key==='Escape')closeModal()});
    function formatDate(date){return date?date.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}):'-'}function formatTimeRange(start,end){if(!start)return '-';const options={hour:'numeric',minute:'2-digit'};return start.toLocaleTimeString('en-US',options)+(end?' - '+end.toLocaleTimeString('en-US',options):'')}
});
</script>
@endsection
