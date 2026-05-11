<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Referral source report for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$download = optional_param('download', '', PARAM_ALPHA);
$referralfilter = optional_param('referral', '', PARAM_ALPHANUMEXT);
$emailfilter = optional_param('email', '', PARAM_RAW_TRIMMED);
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto = optional_param('dateto', 0, PARAM_INT);

$baseurl = new moodle_url('/auth/emailfirst/report.php', array_filter([
    'referral' => $referralfilter,
    'email' => $emailfilter,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
]));

$PAGE->set_url($baseurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('referral_report', 'auth_emailfirst'));
$PAGE->set_heading(get_string('referral_report', 'auth_emailfirst'));

// Referral source labels.
$referrallabels = [
    'search_engine' => get_string('referral_search_engine', 'auth_emailfirst'),
    'social_media' => get_string('referral_social_media', 'auth_emailfirst'),
    'friend_family' => get_string('referral_friend_family', 'auth_emailfirst'),
    'colleague' => get_string('referral_colleague', 'auth_emailfirst'),
    'online_ad' => get_string('referral_online_ad', 'auth_emailfirst'),
    'blog_article' => get_string('referral_blog_article', 'auth_emailfirst'),
    'email_newsletter' => get_string('referral_email_newsletter', 'auth_emailfirst'),
    'event_conference' => get_string('referral_event_conference', 'auth_emailfirst'),
    'youtube' => get_string('referral_youtube', 'auth_emailfirst'),
    'podcast' => get_string('referral_podcast', 'auth_emailfirst'),
    'other' => get_string('referral_other', 'auth_emailfirst'),
];

// Build table.
$table = new \flexible_table('auth_emailfirst_referral_report');
$table->define_columns(['fullname', 'email', 'referral_source', 'timecreated']);
$table->define_headers([
    get_string('report_fullname', 'auth_emailfirst'),
    get_string('report_email', 'auth_emailfirst'),
    get_string('report_referral', 'auth_emailfirst'),
    get_string('report_date', 'auth_emailfirst'),
]);

$table->define_baseurl($baseurl);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->no_sorting('fullname');
$table->set_attribute('class', 'generaltable generalbox');
$table->set_attribute('id', 'referral-report-table');
$table->pageable(true);
$table->is_downloadable(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);
$table->is_downloading(
    $download,
    'referral_report_' . date('Y-m-d'),
    get_string('referral_report', 'auth_emailfirst')
);

$table->setup();

// SQL with filters.
$where = ['u.deleted = 0'];
$params = [];

if (!empty($referralfilter)) {
    $where[] = 's.referral_source = :referral';
    $params['referral'] = $referralfilter;
}

if (!empty($emailfilter)) {
    $where[] = $DB->sql_like('u.email', ':email', false);
    $params['email'] = '%' . $DB->sql_like_escape($emailfilter) . '%';
}

if (!empty($datefrom)) {
    $where[] = 's.timecreated >= :datefrom';
    $params['datefrom'] = $datefrom;
}

if (!empty($dateto)) {
    // End of the selected day.
    $where[] = 's.timecreated <= :dateto';
    $params['dateto'] = $dateto + DAYSECS - 1;
}

$wheresql = implode(' AND ', $where);

$countsql = "SELECT COUNT(s.id)
               FROM {auth_emailfirst_survey} s
               JOIN {user} u ON u.id = s.userid
              WHERE $wheresql";

$totalcount = $DB->count_records_sql($countsql, $params);

// Sorting.
$sort = $table->get_sql_sort();
$orderby = $sort ? "ORDER BY $sort" : 'ORDER BY s.timecreated DESC';

$sql = "SELECT s.id, s.referral_source, s.timecreated,
               u.id AS userid, u.username, u.email, u.firstname, u.lastname,
               u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
          FROM {auth_emailfirst_survey} s
          JOIN {user} u ON u.id = s.userid
         WHERE $wheresql
         $orderby";

$table->pagesize(50, $totalcount);
$pagestart = $table->get_page_start();
$pagesize = $table->get_page_size();

if ($table->is_downloading()) {
    $records = $DB->get_records_sql($sql, $params);
} else {
    $records = $DB->get_records_sql($sql, $params, $pagestart, $pagesize);
}

// Render.
if (!$table->is_downloading()) {
    echo $OUTPUT->header();

    // Summary stats.
    $allcount = $DB->count_records_sql(
        "SELECT COUNT(s.id) FROM {auth_emailfirst_survey} s JOIN {user} u ON u.id = s.userid WHERE u.deleted = 0"
    );
    $sourcebreakdown = $DB->get_records_sql(
        "SELECT s.referral_source, COUNT(*) AS cnt
           FROM {auth_emailfirst_survey} s
           JOIN {user} u ON u.id = s.userid
          WHERE u.deleted = 0
       GROUP BY s.referral_source
       ORDER BY cnt DESC"
    );

    echo '<div class="card mb-4"><div class="card-body">';
    echo '<div class="d-flex flex-wrap align-items-center gap-3">';
    echo '<div class="mr-4"><strong>' . get_string('report_total', 'auth_emailfirst') . ':</strong> ' .
         $allcount . '</div>';
    if (!empty($sourcebreakdown)) {
        echo '<div class="mr-4"><strong>' . get_string('report_top_source', 'auth_emailfirst') . ':</strong> ';
        $top = reset($sourcebreakdown);
        $topkey = $top->referral_source;
        echo s($referrallabels[$topkey] ?? $topkey) . ' (' . $top->cnt . ')';
        echo '</div>';
    }
    if ($totalcount !== $allcount) {
        echo '<div><strong>' . get_string('report_filtered', 'auth_emailfirst') . ':</strong> ' .
             $totalcount . '</div>';
    }
    echo '</div></div></div>';

    // Filter form.
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<form method="get" action="' . $baseurl->out_omit_querystring() . '" class="form-inline">';
    echo '<div class="d-flex flex-wrap align-items-end gap-3">';

    // Referral source dropdown.
    echo '<div class="form-group mr-3 mb-2">';
    echo '<label for="filter-referral" class="mr-2">' . get_string('report_referral', 'auth_emailfirst') . '</label>';
    echo '<select name="referral" id="filter-referral" class="form-control">';
    echo '<option value="">' . get_string('all') . '</option>';
    foreach ($referrallabels as $key => $label) {
        $selected = ($referralfilter === $key) ? ' selected' : '';
        echo '<option value="' . s($key) . '"' . $selected . '>' . s($label) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Email search.
    echo '<div class="form-group mr-3 mb-2">';
    echo '<label for="filter-email" class="mr-2">' . get_string('report_email', 'auth_emailfirst') . '</label>';
    echo '<input type="text" name="email" id="filter-email" class="form-control" value="' .
         s($emailfilter) . '" placeholder="' . get_string('report_email_placeholder', 'auth_emailfirst') . '">';
    echo '</div>';

    // Date from.
    echo '<div class="form-group mr-3 mb-2">';
    echo '<label for="filter-datefrom" class="mr-2">' . get_string('report_datefrom', 'auth_emailfirst') . '</label>';
    $datefromval = $datefrom ? date('Y-m-d', $datefrom) : '';
    echo '<input type="date" name="datefrom_date" id="filter-datefrom" class="form-control" value="' .
         s($datefromval) . '">';
    echo '</div>';

    // Date to.
    echo '<div class="form-group mr-3 mb-2">';
    echo '<label for="filter-dateto" class="mr-2">' . get_string('report_dateto', 'auth_emailfirst') . '</label>';
    $datetoval = $dateto ? date('Y-m-d', $dateto) : '';
    echo '<input type="date" name="dateto_date" id="filter-dateto" class="form-control" value="' .
         s($datetoval) . '">';
    echo '</div>';

    // Buttons.
    echo '<div class="form-group mb-2">';
    echo '<button type="submit" class="btn btn-primary mr-2">' . get_string('report_apply', 'auth_emailfirst') . '</button>';
    echo '<a href="' . (new moodle_url('/auth/emailfirst/report.php'))->out() . '" class="btn btn-outline-secondary">' .
         get_string('report_reset', 'auth_emailfirst') . '</a>';
    echo '</div>';

    echo '</div></form>';
    echo '</div></div>';

    // JS to convert date inputs to timestamps.
    echo '<script>
    document.querySelector("form.form-inline").addEventListener("submit", function(e) {
        var df = document.getElementById("filter-datefrom");
        var dt = document.getElementById("filter-dateto");
        if (df.value) {
            var inp = document.createElement("input");
            inp.type = "hidden"; inp.name = "datefrom";
            inp.value = Math.floor(new Date(df.value + "T00:00:00").getTime() / 1000);
            this.appendChild(inp);
        }
        if (dt.value) {
            var inp2 = document.createElement("input");
            inp2.type = "hidden"; inp2.name = "dateto";
            inp2.value = Math.floor(new Date(dt.value + "T00:00:00").getTime() / 1000);
            this.appendChild(inp2);
        }
        df.removeAttribute("name");
        dt.removeAttribute("name");
    });
    </script>';
}

foreach ($records as $record) {
    $sourcekey = $record->referral_source;
    $sourcelabel = $referrallabels[$sourcekey] ?? $sourcekey;

    $row = [
        fullname($record),
        s($record->email),
        s($sourcelabel),
        userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
    ];
    $table->add_data($row);
}

$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
