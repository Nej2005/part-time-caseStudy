<?php
session_start();
require_once '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['edit_mode'])) {
    $_SESSION['edit_mode'] = false;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    ob_start();

    $subjects_query = "SELECT * FROM Subjects";
    $subjects_result = $conn->query($subjects_query);
    $subjects = [];
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row;
    }

    $sections_query = "SELECT * FROM Sections";
    $sections_result = $conn->query($sections_query);
    $sections = [];
    while ($row = $sections_result->fetch_assoc()) {
        $sections[] = $row;
    }

    $professor_id = $_GET['professor_id'] ?? null;
    if ($professor_id) {
        $professor_query = "SELECT p.*, u.employee_id, 
                   (SELECT college_department FROM Form_Loads 
                    WHERE professor_id = p.professor_id 
                    ORDER BY created_at DESC LIMIT 1) AS college_department
                   FROM PartTime_Professor p
                   JOIN Users u ON p.email_address = u.email_address
                   WHERE p.professor_id = $professor_id";
        $professor_result = $conn->query($professor_query);
        $professor = $professor_result->fetch_assoc();
    } else {
        $professor = ['first_name' => '', 'last_name' => ''];
    }

    $selected_school_year = isset($_GET['school_year']) ? $_GET['school_year'] : null;
    $selected_semester = isset($_GET['semester']) ? $_GET['semester'] : null;
    $form_details = [];
    $form_to_display = null;

    // After fetching the form data, ensure we're using the selected semester/year
    if ($selected_school_year && $selected_semester) {
        $form_query = "SELECT * FROM Form_Loads 
                      WHERE professor_id = ? 
                      AND school_year = ? 
                      AND semester = ? 
                      ORDER BY created_at DESC 
                      LIMIT 1";
        $stmt = $conn->prepare($form_query);
        $stmt->bind_param("iss", $professor_id, $selected_school_year, $selected_semester);
        $stmt->execute();
        $form_result = $stmt->get_result();

        if ($form_result->num_rows > 0) {
            $form_to_display = $form_result->fetch_assoc();
            $form_id = $form_to_display['form_id'] ?? '';

            // Modified query to exclude disabled rows
            $details_query = "SELECT * FROM Form_Load_Details 
                            WHERE form_id = ? 
                            AND (disabled IS NULL OR disabled = 0)
                            ORDER BY detail_id";
            $stmt = $conn->prepare($details_query);
            $stmt->bind_param("i", $form_id);
            $stmt->execute();
            $details_result = $stmt->get_result();
            while ($row = $details_result->fetch_assoc()) {
                $form_details[] = $row;
            }
        }
    }
?>
    <div class="content-area">
        <header>
            <div class="container">
                <div class="header-content">
                    <div class="logo-container">
                        <button class="back-button" onclick="window.location.href='pt-dash.php'">&larr;</button>
                        <img class="logo" src="Logo.ico" alt="School Logo">
                        <div class="school-name">Faculty Portal</div>
                    </div>
                    <div class="date-time" id="date"></div>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <h1 class="page-title">Faculty Loading Form</h1>

                <div class="form-container">
                    <div class="form-header">
                        <div class="form-title">PART-TIME OFFICIAL FACULTY LOADING FORM</div>
                        <div class="form-subtitle" id="semester-subtitle">
                            <?php if (isset($form_to_display) && $form_to_display): ?>
                                <?php
                                $semesterDisplay = $form_to_display['semester'];
                                if ($semesterDisplay === 'Summer') {
                                    echo "SUMMER TERM A.Y ";
                                } else {
                                    echo strtoupper($semesterDisplay) . " SEMESTER A.Y ";
                                }
                                echo htmlspecialchars($form_to_display['school_year']);
                                ?>
                            <?php elseif (isset($current_form) && $current_form): ?>
                                <?php
                                $semesterDisplay = $current_form['semester'];
                                if ($semesterDisplay === 'Summer') {
                                    echo "SUMMER TERM A.Y ";
                                } else {
                                    echo strtoupper($semesterDisplay) . " SEMESTER A.Y ";
                                }
                                echo htmlspecialchars($current_form['school_year']);
                                ?>
                            <?php else: ?>
                                NO DATA AVAILABLE
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Name:</span>
                            <div class="value">
                                <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="label">Status:</span>
                            <div class="value">Part-Time Faculty</div>
                        </div>
                        <div class="info-item">
                            <span class="label">College/Department:</span>
                            <div class="value">
                                <?php
                                if (isset($form_to_display) && $form_to_display) {
                                    echo htmlspecialchars($form_to_display['college_department']);
                                } elseif (isset($current_form) && $current_form) {
                                    echo htmlspecialchars($current_form['college_department']);
                                } elseif (isset($professor['college_department'])) {
                                    echo htmlspecialchars($professor['college_department']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="label">Period:</span>
                            <div class="value" id="period-value">
                                <?php echo ($form_to_display ? htmlspecialchars($form_to_display['period']) : ($current_form ? htmlspecialchars($current_form['period']) : 'N/A')); ?>
                            </div>
                        </div>
                    </div>

                    <div class="schedule-section">
                        <label class="schedule-label">SCHEDULE</label>
                        <div class="schedule-box">
                            <?php if ($form_to_display || $current_form): ?>
                                <table class="loading-table" id="loading-table">
                                    <thead>
                                        <tr>
                                            <th>CODE</th>
                                            <th>SUBJECT DESCRIPTION</th>
                                            <th>LEC.</th>
                                            <th>LAB.</th>
                                            <th>NO. OF HRS/WK</th>
                                            <th>MON</th>
                                            <th>TUE</th>
                                            <th>WED</th>
                                            <th>THU</th>
                                            <th>FRI</th>
                                            <th>SAT</th>
                                            <th>SUN</th>
                                            <th>ROOM</th>
                                            <th>SECTION</th>
                                            <th>PERIOD</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($form_details)): ?>
                                            <?php foreach ($form_details as $detail): ?>
                                                <tr data-detail-id="<?php echo $detail['detail_id']; ?>" <?php echo ($detail['disabled'] ? 'class="row-disabled"' : ''); ?>>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <div class="subject-container">
                                                                <input type="text"
                                                                    name="subject_description_base[]"
                                                                    class="subject-description-base"
                                                                    value="<?php
                                                                            $baseDesc = preg_replace('/\s*\(LEC\)|\s*\(LAB\)/', '', $detail['subject_description']);
                                                                            echo htmlspecialchars(trim($baseDesc));
                                                                            ?>"
                                                                    readonly>
                                                                <select name="subject_type[]" class="subject-type-toggle">
                                                                    <option value="LEC" <?php echo (strpos($detail['subject_description'], 'LEC') !== false) ? 'selected' : ''; ?>>LEC</option>
                                                                    <option value="LAB" <?php echo (strpos($detail['subject_description'], 'LAB') !== false) ? 'selected' : ''; ?>>LAB</option>
                                                                    <option value="" <?php echo (strpos($detail['subject_description'], 'LEC') === false && strpos($detail['subject_description'], 'LAB') === false) ? 'selected' : ''; ?>>None</option>
                                                                </select>
                                                            </div>
                                                        <?php else : ?>
                                                            <input type="text"
                                                                name="subject_description[]"
                                                                class="subject-description-combined"
                                                                value="<?php
                                                                        $desc = $detail['subject_description'];
                                                                        // Remove trailing " ()" if it exists
                                                                        if (substr($desc, -3) === ' ()') {
                                                                            $desc = substr($desc, 0, -3);
                                                                        }
                                                                        echo htmlspecialchars($desc);
                                                                        ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <div class="subject-container">
                                                                <input type="text"
                                                                    name="subject_description_base[]"
                                                                    class="subject-description-base"
                                                                    value="<?php
                                                                            $baseDesc = preg_replace('/\s*\(LEC\)|\s*\(LAB\)/', '', $detail['subject_description']);
                                                                            echo htmlspecialchars(trim($baseDesc));
                                                                            ?>"
                                                                    readonly>
                                                                <select name="subject_type[]" class="subject-type-toggle">
                                                                    <option value="LEC" <?php echo (strpos($detail['subject_description'], 'LEC') !== false) ? 'selected' : ''; ?>>LEC</option>
                                                                    <option value="LAB" <?php echo (strpos($detail['subject_description'], 'LAB') !== false) ? 'selected' : ''; ?>>LAB</option>
                                                                    <option value="" <?php echo (strpos($detail['subject_description'], 'LEC') === false && strpos($detail['subject_description'], 'LAB') === false) ? 'selected' : ''; ?>>None</option>
                                                                </select>
                                                            </div>
                                                        <?php else : ?>
                                                            <input type="text"
                                                                name="subject_description[]"
                                                                class="subject-description-combined"
                                                                value="<?php
                                                                        $desc = $detail['subject_description'];
                                                                        if (substr($desc, -3) === ' ()') {
                                                                            $desc = substr($desc, 0, -3);
                                                                        }
                                                                        echo htmlspecialchars($desc);
                                                                        ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="lec_hours[]"
                                                            value="<?php echo htmlspecialchars($detail['lec_hours']); ?>"
                                                            min="0"
                                                            step="0.5"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="lab_hours[]"
                                                            value="<?php echo htmlspecialchars($detail['lab_hours']); ?>"
                                                            min="0"
                                                            step="0.1"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="hrs_per_week[]"
                                                            value="<?php echo htmlspecialchars($detail['hrs_per_week']); ?>"
                                                            min="0"
                                                            step="0.1"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="monday[]"
                                                            value="<?php echo htmlspecialchars($detail['monday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="tuesday[]"
                                                            value="<?php echo htmlspecialchars($detail['tuesday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="wednesday[]"
                                                            value="<?php echo htmlspecialchars($detail['wednesday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="thursday[]"
                                                            value="<?php echo htmlspecialchars($detail['thursday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="friday[]"
                                                            value="<?php echo htmlspecialchars($detail['friday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="saturday[]"
                                                            value="<?php echo htmlspecialchars($detail['saturday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="sunday[]"
                                                            value="<?php echo htmlspecialchars($detail['sunday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="room[]"
                                                            value="<?php echo htmlspecialchars($detail['room']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <select name="section[]">
                                                                <option value="" hidden>Select Section</option>
                                                                <?php foreach ($sections as $section) : ?>
                                                                    <option
                                                                        value="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                                        <?php echo ($detail['section'] == $section['section_name']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <input
                                                                type="text"
                                                                name="section[]"
                                                                value="<?php echo htmlspecialchars($detail['section']); ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="period_col[]"
                                                            value="<?php echo ($form_to_display ? htmlspecialchars($form_to_display['period']) : ($current_form ? htmlspecialchars($current_form['period']) : '')); ?>"
                                                            readonly>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td colspan="2">TOTAL</td>
                                                <td id="totalLec">
                                                    <?php echo array_sum(array_column($form_details, 'lec_hours')); ?>
                                                </td>
                                                <td id="totalLab">
                                                    <?php echo array_sum(array_column($form_details, 'lab_hours')); ?>
                                                </td>
                                                <td id="totalHrsWk">
                                                    <?php echo array_sum(array_column($form_details, 'hrs_per_week')); ?>
                                                </td>
                                                <td colspan="10"></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="16" class="no-data">No schedule data available for this semester</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data">
                                    No form loading data available for this professor
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bottom-section">
                        <table class="bottom-table">
                            <tr>
                                <td>NO. OF PREPARATIONS</td>
                                <td><input type="number" id="numPreparations"
                                        value="<?php echo count(array_unique(array_column($form_details, 'subject_code'))); ?>"
                                        class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>LOWEST TEACHING HRS/DAY</td>
                                <td><input type="number" id="lowestHrs" value="0" class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>HIGHEST TEACHING HRS/DAY</td>
                                <td><input type="number" id="highestHrs" value="0" class="small-input" readonly>
                                </td>
                            </tr>
                        </table>

                        <table class="bottom-table">
                            <tr>
                                <td>TOTAL LOAD UNITS</td>
                                <td><input type="number" id="totalLoadUnits" value="<?php echo calculateTotalLoadUnits($form_details, $subjects); ?>" class="small-input" readonly></td>
                                </td>
                            </tr>
                            <tr>
                                <td>TOTAL LOAD HRS</td>
                                <td><input type="number" id="totalLoadHrs"
                                        value="<?php echo array_sum(array_column($form_details, 'hrs_per_week')); ?>"
                                        class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>CURRENTLY EMPLOYED IN OTHER GOV'T INSTITUTION</td>
                                <td class="checkbox-container">
                                    <input type="checkbox" id="yes" name="employed_elsewhere" value="1">
                                    <label for="yes" class="checkbox-label">YES</label>
                                    <input type="checkbox" id="no" name="not_employed_elsewhere" value="1" checked>
                                    <label for="no">NO</label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="button-container">
                        <div class="action-buttons">
                            <?php if ($_SESSION['user_type'] === 'Admin_Secretary'): ?>
                                <button class="btn btn-edit" id="edit-btn" onclick="toggleEditMode()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="confirmDelete()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button class="btn btn-save" id="save-btn" style="display: none;" onclick="saveChanges()">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button class="btn btn-cancel" id="cancel-btn" style="display: none;"
                                    onclick="toggleEditMode()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="right-buttons">
                            <button class="btn-export" onclick="exportForm()">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                    </div>

                    <form id="delete-form" method="POST" style="display: none;">
                        <input type="hidden" name="delete_form" value="1">
                        <input type="hidden" name="form_id"
                            value="<?php echo ($form_to_display ? $form_to_display['form_id'] : $current_form['form_id']); ?>">
                    </form>
                </div>
            </div>
        </main>
    </div>
<?php
    $content = ob_get_clean();
    echo $content;
    exit();
}

if (!isset($_SESSION['edit_mode'])) {
    $_SESSION['edit_mode'] = false;
}

if (isset($_GET['redirected'])) {
    unset($_GET['redirected']);
} elseif (!isset($_GET['professor_id'])) {
    header("Location: emp-formload.php?redirected=1");
    exit();
}

// Check if professor_id is provided
if (!isset($_GET['professor_id'])) {
    header("Location: emp-formload.php");
    exit();
}

$subjects = [];
$sections = [];

// Get all subjects for dropdown
$subjects_query = "SELECT * FROM Subjects";
$subjects_result = $conn->query($subjects_query);
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get all sections for dropdown
$sections_query = "SELECT * FROM Sections";
$sections_result = $conn->query($sections_query);
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row;
}

$professor_id = $_GET['professor_id'];

// Get professor details
$professor_query = "SELECT p.*, u.employee_id, 
                    (SELECT college_department FROM Form_Loads 
                     WHERE professor_id = $professor_id 
                     ORDER BY created_at DESC LIMIT 1) AS department
                    FROM PartTime_Professor p
                    JOIN Users u ON p.email_address = u.email_address
                    WHERE p.professor_id = $professor_id";
$professor_result = $conn->query($professor_query);
$professor = $professor_result->fetch_assoc();

$school_years_query = "SELECT DISTINCT school_year FROM Form_Loads WHERE professor_id = $professor_id ORDER BY school_year DESC";
$school_years_result = $conn->query($school_years_query);
$school_years = [];
while ($row = $school_years_result->fetch_assoc()) {
    $school_years[] = $row['school_year'];
}

// Initialize variables
$selected_school_year = isset($_GET['school_year']) ? $_GET['school_year'] : null;
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : null;
$form_details = [];
$form_to_display = null;
$current_form = null;

// Get current form (most recent) if no specific form is selected
if (!$selected_school_year || !$selected_semester) {
    // First try to get the latest school year and semester
    $latest_year_query = "SELECT school_year, semester FROM Form_Loads 
                         WHERE professor_id = $professor_id 
                         ORDER BY school_year DESC, 
                         CASE WHEN semester = '1st' THEN 1 ELSE 2 END, 
                         created_at DESC 
                         LIMIT 1";
    $latest_year_result = $conn->query($latest_year_query);

    if ($latest_year_result->num_rows > 0) {
        $latest_year = $latest_year_result->fetch_assoc();
        $selected_school_year = $latest_year['school_year'];
        $selected_semester = $latest_year['semester'];
    }
}

// If school year and semester are selected or we have current form
if ($selected_school_year && $selected_semester) {
    $form_query = "SELECT * FROM Form_Loads 
                  WHERE professor_id = $professor_id 
                  AND school_year = '$selected_school_year' 
                  AND semester = '$selected_semester' 
                  ORDER BY created_at DESC 
                  LIMIT 1";
    $form_result = $conn->query($form_query);

    if ($form_result->num_rows > 0) {
        $form_to_display = $form_result->fetch_assoc();
        $form_id = $form_to_display['form_id'];

        // Modified query to exclude disabled rows
        $details_query = "SELECT * FROM Form_Load_Details 
                         WHERE form_id = $form_id 
                         AND (disabled IS NULL OR disabled = 0)
                         ORDER BY detail_id";
        $details_result = $conn->query($details_query);
        while ($row = $details_result->fetch_assoc()) {
            $form_details[] = $row;
        }
    }
}

function hasFormData($conn, $professor_id, $school_year, $semester)
{
    $query = "SELECT COUNT(*) as count FROM Form_Loads 
              WHERE professor_id = ? 
              AND school_year = ? 
              AND semester = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $professor_id, $school_year, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

$hasFirstSemData = hasFormData($conn, $professor_id, $selected_school_year, '1st');
$hasSecondSemData = hasFormData($conn, $professor_id, $selected_school_year, '2nd');
$hasSummerData = hasFormData($conn, $professor_id, $selected_school_year, 'Summer');

$allSchoolYears = [];
$school_years_query = "SELECT DISTINCT school_year FROM Form_Loads WHERE professor_id = $professor_id ORDER BY school_year DESC";
$school_years_result = $conn->query($school_years_query);
while ($row = $school_years_result->fetch_assoc()) {
    $allSchoolYears[] = $row['school_year'];
    // Pre-check data for each year
    $yearHasData[$row['school_year']] = [
        '1st' => hasFormData($conn, $professor_id, $row['school_year'], '1st'),
        '2nd' => hasFormData($conn, $professor_id, $row['school_year'], '2nd'),
        'Summer' => hasFormData($conn, $professor_id, $row['school_year'], 'Summer')
    ];
}

// Handle delete request
if (isset($_POST['delete_form'])) {
    $form_id_to_delete = $_POST['form_id'];

    // Get the current school year and semester from the form being viewed
    $current_school_year = $selected_school_year;
    $current_semester = $selected_semester;

    $conn->begin_transaction();

    try {
        // First verify this is the correct form to delete
        $verify_query = "SELECT school_year, semester FROM Form_Loads 
                        WHERE form_id = ? AND professor_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $form_id_to_delete, $professor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Form not found or doesn't belong to this professor");
        }

        $form_info = $result->fetch_assoc();

        // Verify we're deleting the form we think we're deleting
        if (
            $form_info['school_year'] !== $current_school_year ||
            $form_info['semester'] !== $current_semester
        ) {
            throw new Exception("Attempt to delete wrong form - mismatch in school year/semester");
        }

        // Proceed with deletion
        $delete_details = "DELETE FROM Form_Load_Details WHERE form_id = ?";
        $stmt = $conn->prepare($delete_details);
        $stmt->bind_param("i", $form_id_to_delete);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting form details: " . $stmt->error);
        }

        $delete_form = "DELETE FROM Form_Loads WHERE form_id = ?";
        $stmt = $conn->prepare($delete_form);
        $stmt->bind_param("i", $form_id_to_delete);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting form: " . $stmt->error);
        }

        $conn->commit();
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

function calculateTotalLoadUnits($form_details, $subjects)
{
    if (empty($form_details)) return 0;

    $subjectUnitsMap = [];
    foreach ($subjects as $subject) {
        $subjectUnitsMap[$subject['subject_code']] = $subject['units'];
    }

    $total = 0;
    $processedSubjects = [];

    foreach ($form_details as $detail) {
        // Skip disabled rows
        if (isset($detail['disabled']) && $detail['disabled']) {
            continue;
        }

        $subjectCode = $detail['subject_code'];

        if (!in_array($subjectCode, $processedSubjects)) {
            if (isset($subjectUnitsMap[$subjectCode])) {
                $total += (float)$subjectUnitsMap[$subjectCode];
            }
            $processedSubjects[] = $subjectCode;
        }
    }

    return $total;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Loading Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            background-color: #f5f5f5;
            font-family: 'Poppins', sans-serif;
            color: #333;
            overflow-x: hidden;
        }


        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            transition: margin-left 0.3s ease;
        }


        header {
            background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
        }


        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        .logo-container {
            display: flex;
            align-items: center;
        }


        .logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }


        .school-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
        }


        .date-time {
            font-family: 'Montserrat', sans-serif;
            font-weight: 300;
            font-size: 0.9rem;
            text-align: right;
        }


        .back-button {
            background-color: white;
            color: #3b5525;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }


        .back-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }


        main {
            padding: 30px 0;
            position: relative;
            transition: all 0.3s ease;
        }


        .page-title {
            margin-bottom: 25px;
            color: #1a2a0d;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        /* Add to your existing CSS */
        .row-disabled {
            background-color: #f0f0f0 !important;
            opacity: 0.7;
        }


        .row-disabled input:not(.row-checkbox),
        .row-disabled select {
            background-color: #e0e0e0 !important;
            pointer-events: none;
        }

        .row-checkbox {
            pointer-events: auto !important;
            opacity: 1 !important;
        }

        .row-checkboxes {
            position: absolute;
            left: -40px;
            top: 0;
            width: 40px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 60px;
            height: 4px;
            background-color: #d0c74f;
            border-radius: 2px;
        }


        .form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 40px;
        }


        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }


        .form-title {
            font-size: 22px;
            font-weight: bold;
            color: #3b5525;
            margin-bottom: 5px;
            font-family: 'Montserrat', sans-serif;
        }


        .form-subtitle {
            font-size: 16px;
            color: #666;
            font-family: 'Montserrat', sans-serif;
        }


        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }


        .info-item {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 8px;
        }


        .label {
            font-weight: 500;
            color: #555;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }


        .value {
            color: #333;
            font-size: 16px;
            padding: 5px 0;
        }


        .schedule-section {
            margin-top: 30px;
        }


        .schedule-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 10px;
            display: block;
        }


        .schedule-box {
            width: 100%;
            min-height: 400px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
            overflow-x: auto;
            overflow-y: visible;
        }


        .loading-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            table-layout: fixed;
        }


        .loading-table th,
        .loading-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            min-width: 60px;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .loading-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }


        .loading-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }


        .loading-table tr:hover {
            background-color: #f1f1f1;
        }


        .loading-table input,
        .loading-table select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            box-sizing: border-box;
        }


        .loading-table input[readonly],
        .loading-table select[disabled] {
            background-color: #f5f5f5;
            border: none;
            color: #333;
            cursor: default;
        }


        .edit-mode .loading-table input:not([readonly]),
        .edit-mode .loading-table select:not([disabled]) {
            background-color: white;
            border: 1px solid #ccc;
            color: #000;
            cursor: text;
        }


        .loading-table td {
            overflow: visible !important;
            position: relative !important;
        }

        /* Add this to your CSS */
        .checkbox-column input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-column input[type="checkbox"]:checked {
            pointer-events: none;
            opacity: 0.7;
        }


        .loading-table select {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1000 !important;
        }


        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }


        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .btn i {
            margin-right: 6px;
        }


        .btn-edit {
            background-color: #4A6DA7;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }


        .btn-edit:hover {
            background-color: #3a5a8f;
        }


        .btn-delete {
            background-color: #f44336;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }


        .btn-delete:hover {
            background-color: #d32f2f;
        }


        .btn-save {
            background-color: #3b5525;
            color: white;
        }


        .btn-save:hover {
            background-color: #1a2a0d;
        }


        .btn-cancel {
            background-color: #f1f1f1;
            color: #333;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }


        .btn-cancel:hover {
            background-color: #e0e0e0;
        }


        .right-buttons {
            display: flex;
            gap: 10px;
        }


        .btn-export {
            padding: 12px 24px;
            background-color: #2C5E1A;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }


        .btn-export:hover {
            background-color: #1e3d12;
        }


        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
            font-size: 1.2rem;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin: 20px;
        }


        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }


        .bottom-table {
            width: 48%;
            border-collapse: collapse;
        }


        .bottom-table td {
            padding: 10px;
            border: 1px solid #000;
            font-size: 14px;
        }


        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .checkbox-label {
            margin-right: 10px;
        }


        .small-input {
            width: 100px;
            padding: 8px;
            font-size: 14px;
        }


        .button-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }


        .button-container-left {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }


        .add-row-btn {
            background-color: #4A6DA7;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }


        .add-row-btn:hover {
            background-color: #3a5a8f;
        }


        .btn-send {
            padding: 12px 24px;
            background-color: #d0c74f;
            color: #1a2a0d;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }


        .btn-send:hover {
            background-color: #b8b14a;
        }


        .subject-container {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }


        .subject-description-combined {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }


        .subject-container input {
            flex: 1;
            min-width: 0;
        }


        .subject-container select {
            width: 80px;
        }


        .subject-code {
            min-width: 75px;
        }


        .subject-description-base {
            flex: 1;
            min-width: 0;
        }


        .subject-type-toggle {
            display: none;
            width: 80px;
            flex-shrink: 0;
        }


        .edit-mode .subject-type-toggle {
            display: inline-block !important;
        }


        .loading {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }


        .error {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #d32f2f;
        }


        .sidebar {
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100%;
            background-color: #e9e7c0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            padding-top: 70px;
        }


        .sidebar.active {
            left: 0;
        }

        .toggle-btn.active {
            left: 250px;
        }

        .sidebar-content {
            padding: 20px;
        }


        .sidebar-header {
            text-align: center;
            font-weight: 600;
            color: #3b5525;
            padding: 10px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #d0c74f;
        }

        /* Toggle button styles */
        .toggle-btn {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 60px;
            background-color: #3b5525;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            cursor: pointer;
            z-index: 1001;
            transition: left 0.3s ease;
        }


        .toggle-btn:hover {
            background-color: #d0c74f;
        }

        .toggle-btn.active {
            left: 250px;
            /* Move 250px to the right when active */
        }

        .toggle-icon {
            color: white;
            font-size: 18px;
        }


        .year-selector {
            margin-bottom: 20px;
        }


        .year-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #3b5525;
        }


        select.year-dropdown {
            width: 100%;
            padding: 8px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            background-color: white;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
            cursor: pointer;
        }


        .semester-card {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }


        .semester-title {
            text-align: center;
            padding: 15px 10px;
            font-weight: 600;
            color: #333;
        }


        .view-btn {
            display: block;
            text-align: center;
            background-color: #f0f0f0;
            padding: 8px;
            color: #3b5525;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }


        .view-btn:hover {
            background-color: #d0c74f;
            color: #1a2a0d;
        }


        .view-btn.disabled {
            background-color: #e0e0e0 !important;
            color: #999 !important;
            cursor: not-allowed !important;
            pointer-events: none;
        }


        .view-btn i {
            margin-right: 5px;
        }


        .back-to-current {
            display: block;
            background-color: #3b5525;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            margin-top: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            width: calc(100% - 40px);
            margin-left: 20px;
        }


        .back-to-current:hover {
            background-color: #1a2a0d;
        }


        .content-area {
            transition: margin-left 0.3s ease;
            margin-left: 0;
        }


        .content-area.sidebar-active {
            margin-left: 250px;
        }


        .delete-row-btn {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }

        .disable-row-btn {
            background-color: #ff9800;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }

        .disable-row-btn:hover {
            background-color: #e68a00;
        }

        .row-selected {
            background-color: rgba(210, 199, 79, 0.3) !important;
        }

        .enable-row-btn {
            background-color: rgb(40, 131, 43) !important;
            color: white !important;
        }

        .enable-row-btn:hover {
            background-color: rgb(61, 131, 65) !important;
        }

        .loading {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }

        .loading[style*="color: green"] {
            color: #3b5525 !important;
            font-weight: 500;
        }

        .error {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #d32f2f;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }


            .logo-container {
                margin-bottom: 10px;
                justify-content: center;
            }


            .date-time {
                text-align: center;
                margin-top: 10px;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .form-container {
                padding: 20px;
            }


            .content-area.sidebar-active {
                margin-left: 0;
            }


            .loading-table {
                font-size: 14px;
                margin-left: 0;
            }

            .loading-table-container {
                position: relative;
                margin-left: 40px;
            }


            .loading-table th,
            .loading-table td {
                padding: 5px;
            }


            .bottom-section {
                flex-direction: column;
            }


            .bottom-table {
                width: 100%;
                margin-bottom: 20px;
            }
        }


        /* Column width adjustments */
        .loading-table th:nth-child(1),
        .loading-table td:nth-child(1) {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }


        .loading-table th:nth-child(2),
        .loading-table td:nth-child(2) {
            width: 200px;
            min-width: 200px;
            max-width: 200px;
        }


        .loading-table th:nth-child(3),
        .loading-table td:nth-child(3),
        .loading-table th:nth-child(4),
        .loading-table td:nth-child(4) {
            width: 75px;
            min-width: 75px;
            max-width: 75px;
        }


        .loading-table th:nth-child(5),
        .loading-table td:nth-child(5) {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }


        .loading-table th:nth-child(6),
        .loading-table td:nth-child(6),
        .loading-table th:nth-child(7),
        .loading-table td:nth-child(7),
        .loading-table th:nth-child(8),
        .loading-table td:nth-child(8),
        .loading-table th:nth-child(9),
        .loading-table td:nth-child(9),
        .loading-table th:nth-child(10),
        .loading-table td:nth-child(10),
        .loading-table th:nth-child(11),
        .loading-table td:nth-child(11),
        .loading-table th:nth-child(12),
        .loading-table td:nth-child(12) {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
        }


        .loading-table th:nth-child(13),
        .loading-table td:nth-child(13) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }


        .loading-table th:nth-child(14),
        .loading-table td:nth-child(14) {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }


        .loading-table th:nth-child(15),
        .loading-table td:nth-child(15) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
    </style>

</head>

<body>
    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" id="sidebar-toggle">
            <span class="toggle-icon" id="toggle-icon">❯</span>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-header">PREVIOUS FACULTY LOADING FORM</div>

            <div class="year-selector">
                <label class="year-label">School Year:</label>
                <select class="year-dropdown" id="year-dropdown" onchange="updateSemesterCards()">
                    <?php if (empty($allSchoolYears)): ?>
                        <option value="">No data available</option>
                    <?php else: ?>
                        <?php foreach ($allSchoolYears as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>"
                                <?php echo ($year == $selected_school_year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="semester-card">
                <div class="semester-title">1st Sem</div>
                <div class="view-btn <?php echo $hasFirstSemData ? '' : 'disabled'; ?>"
                    id="first-sem-btn"
                    onclick="<?php echo $hasFirstSemData ? 'viewSemester(\'1st\')' : ''; ?>">
                    <i class="fas <?php echo $hasFirstSemData ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                    <?php echo $hasFirstSemData ? 'View' : 'No Data'; ?>
                </div>
            </div>

            <div class="semester-card">
                <div class="semester-title">2nd Sem</div>
                <div class="view-btn <?php echo $hasSecondSemData ? '' : 'disabled'; ?>"
                    id="second-sem-btn"
                    onclick="<?php echo $hasSecondSemData ? 'viewSemester(\'2nd\')' : ''; ?>">
                    <i class="fas <?php echo $hasSecondSemData ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                    <?php echo $hasSecondSemData ? 'View' : 'No Data'; ?>
                </div>
            </div>

            <div class="semester-card">
                <div class="semester-title">Summer</div>
                <div class="view-btn <?php echo $hasSummerData ? '' : 'disabled'; ?>"
                    id="summer-sem-btn"
                    onclick="<?php echo $hasSummerData ? 'viewSemester(\'Summer\')' : ''; ?>">
                    <i class="fas <?php echo $hasSummerData ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                    <?php echo $hasSummerData ? 'View' : 'No Data'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area">
        <header>
            <div class="container">
                <div class="header-content">
                    <div class="logo-container">
                        <button class="back-button" onclick="goBackToPtDash()">&larr;</button>
                        <img class="logo" src="Logo.ico" alt="School Logo">
                        <div class="school-name">Faculty Portal</div>
                    </div>
                    <div class="date-time" id="date"></div>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <h1 class="page-title">Faculty Loading Form</h1>

                <div class="form-container">
                    <div class="form-header">
                        <div class="form-title">PART-TIME OFFICIAL FACULTY LOADING FORM</div>
                        <div class="form-subtitle" id="semester-subtitle">
                            <?php if (isset($form_to_display) && $form_to_display): ?>
                                <?php
                                $semesterDisplay = $form_to_display['semester'];
                                if ($semesterDisplay === 'Summer') {
                                    echo "SUMMER TERM A.Y ";
                                } else {
                                    echo strtoupper($semesterDisplay) . " SEMESTER A.Y ";
                                }
                                echo htmlspecialchars($form_to_display['school_year']);
                                ?>
                            <?php elseif (isset($current_form) && $current_form): ?>
                                <?php
                                $semesterDisplay = $current_form['semester'];
                                if ($semesterDisplay === 'Summer') {
                                    echo "SUMMER TERM A.Y ";
                                } else {
                                    echo strtoupper($semesterDisplay) . " SEMESTER A.Y ";
                                }
                                echo htmlspecialchars($current_form['school_year']);
                                ?>
                            <?php else: ?>
                                NO DATA AVAILABLE
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Name:</span>
                            <div class="value">
                                <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="label">Status:</span>
                            <div class="value">Part-Time Faculty</div>
                        </div>
                        <div class="info-item">
                            <span class="label">College/Department:</span>
                            <div class="value">
                                <?php
                                if ($form_to_display) {
                                    echo htmlspecialchars($form_to_display['college_department']);
                                } elseif ($current_form) {
                                    echo htmlspecialchars($current_form['college_department']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="label">Period:</span>
                            <div class="value" id="period-value">
                                <?php echo ($form_to_display ? htmlspecialchars($form_to_display['period']) : ($current_form ? htmlspecialchars($current_form['period']) : 'N/A')); ?>
                            </div>
                        </div>
                    </div>

                    <div class="schedule-section">
                        <label class="schedule-label">SCHEDULE</label>
                        <div class="schedule-box">
                            <?php if ($form_to_display || $current_form): ?>
                                <table class="loading-table" id="loading-table">
                                    <thead>
                                        <tr>
                                            <th>CODE</th>
                                            <th>SUBJECT DESCRIPTION</th>
                                            <th>LEC.</th>
                                            <th>LAB.</th>
                                            <th>NO. OF HRS/WK</th>
                                            <th>MON</th>
                                            <th>TUE</th>
                                            <th>WED</th>
                                            <th>THU</th>
                                            <th>FRI</th>
                                            <th>SAT</th>
                                            <th>SUN</th>
                                            <th>ROOM</th>
                                            <th>SECTION</th>
                                            <th>PERIOD</th>

                                        </tr>
                                    <tbody>
                                        <?php if (!empty($form_details)): ?>
                                            <?php foreach ($form_details as $detail): ?>
                                                <tr data-detail-id="<?php echo $detail['detail_id']; ?>" <?php echo ($detail['disabled'] ? 'class="row-disabled"' : ''); ?>>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <select name="subject_code[]" class="subject-code">
                                                                <option value="" hidden>Select Subject</option>
                                                                <?php foreach ($subjects as $subject) : ?>
                                                                    <option value="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                                        data-description="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                                        <?php echo ($detail['subject_code'] == $subject['subject_code']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($subject['subject_code']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <input
                                                                type="text"
                                                                name="subject_code[]"
                                                                class="subject-code"
                                                                value="<?php echo htmlspecialchars($detail['subject_code']); ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <div class="subject-container">
                                                                <input type="text"
                                                                    name="subject_description_base[]"
                                                                    class="subject-description-base"
                                                                    value="<?php
                                                                            $baseDesc = preg_replace('/\s*\(LEC\)|\s*\(LAB\)/', '', $detail['subject_description']);
                                                                            echo htmlspecialchars(trim($baseDesc));
                                                                            ?>"
                                                                    readonly>
                                                                <select name="subject_type[]" class="subject-type-toggle">
                                                                    <option value="LEC" <?php echo (strpos($detail['subject_description'], 'LEC') !== false) ? 'selected' : ''; ?>>LEC</option>
                                                                    <option value="LAB" <?php echo (strpos($detail['subject_description'], 'LAB') !== false) ? 'selected' : ''; ?>>LAB</option>
                                                                </select>
                                                            </div>
                                                        <?php else : ?>
                                                            <input type="text"
                                                                name="subject_description[]"
                                                                class="subject-description-combined"
                                                                value="<?php
                                                                        $desc = $detail['subject_description'];
                                                                        // Remove trailing " ()" if it exists
                                                                        if (substr($desc, -3) === ' ()') {
                                                                            $desc = substr($desc, 0, -3);
                                                                        }
                                                                        echo htmlspecialchars($desc);
                                                                        ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="number"
                                                            name="lec_hours[]"
                                                            value="<?php echo htmlspecialchars($detail['lec_hours']); ?>"
                                                            min="0"
                                                            step="0.5"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="number"
                                                            name="lab_hours[]"
                                                            value="<?php echo htmlspecialchars($detail['lab_hours']); ?>"
                                                            min="0"
                                                            step="0.5"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="number"
                                                            name="hrs_per_week[]"
                                                            value="<?php echo htmlspecialchars($detail['hrs_per_week']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="monday[]"
                                                            value="<?php echo htmlspecialchars($detail['monday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="tuesday[]"
                                                            value="<?php echo htmlspecialchars($detail['tuesday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="wednesday[]"
                                                            value="<?php echo htmlspecialchars($detail['wednesday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="thursday[]"
                                                            value="<?php echo htmlspecialchars($detail['thursday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="friday[]"
                                                            value="<?php echo htmlspecialchars($detail['friday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="saturday[]"
                                                            value="<?php echo htmlspecialchars($detail['saturday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="sunday[]"
                                                            value="<?php echo htmlspecialchars($detail['sunday']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="room[]"
                                                            value="<?php echo htmlspecialchars($detail['room']); ?>"
                                                            <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) ? '' : 'readonly' ?>>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']) : ?>
                                                            <select name="section[]">
                                                                <option value="" hidden>Select Section</option>
                                                                <?php foreach ($sections as $section) : ?>
                                                                    <option
                                                                        value="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                                        <?php echo ($detail['section'] == $section['section_name']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <input
                                                                type="text"
                                                                name="section[]"
                                                                value="<?php echo htmlspecialchars($detail['section']); ?>"
                                                                readonly>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="text"
                                                            name="period_col[]"
                                                            value="<?php echo ($form_to_display ? htmlspecialchars($form_to_display['period']) : ($current_form ? htmlspecialchars($current_form['period']) : '')); ?>"
                                                            readonly>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td colspan="2">TOTAL</td>
                                                <td id="totalLec">
                                                    <?php echo array_sum(array_column($form_details, 'lec_hours')); ?>
                                                </td>
                                                <td id="totalLab">
                                                    <?php echo array_sum(array_column($form_details, 'lab_hours')); ?>
                                                </td>
                                                <td id="totalHrsWk">
                                                    <?php echo array_sum(array_column($form_details, 'hrs_per_week')); ?>
                                                </td>
                                                <td colspan="10"></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="15" class="no-data">No schedule data available for this
                                                    semester
                                                </td>
                                                <td colspan="10"></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data">
                                    No form loading data available for this professor
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="button-container-left" style="margin-top: 15px;">
                        <button class="add-row-btn" id="add-row-btn" onclick="addNewRow()" style="display: none;">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                        <button class="delete-row-btn" id="delete-row-btn" onclick="deleteSelectedRow()" style="display: none;">
                            <i class="fas fa-minus"></i> Delete Row
                        </button>
                        <button class="disable-row-btn" id="disable-row-btn" onclick="toggleRowDisabledState()" style="display: none;">
                            <i class="fas fa-ban"></i> <span class="btn-text">Disable Row</span>
                        </button>
                    </div>

                    <div class="bottom-section">
                        <table class="bottom-table">
                            <tr>
                                <td>NO. OF PREPARATIONS</td>
                                <td><input type="number" id="numPreparations"
                                        value="<?php echo count(array_unique(array_column($form_details, 'subject_code'))); ?>"
                                        class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>LOWEST TEACHING HRS/DAY</td>
                                <td><input type="number" id="lowestHrs" value="0" class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>HIGHEST TEACHING HRS/DAY</td>
                                <td><input type="number" id="highestHrs" value="0" class="small-input" readonly>
                                </td>
                            </tr>
                        </table>

                        <table class="bottom-table">
                            <tr>
                                <td>TOTAL LOAD UNITS</td>
                                <td><input type="number" id="totalLoadUnits" value="<?php echo calculateTotalLoadUnits($form_details, $subjects); ?>" class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>TOTAL LOAD HRS</td>
                                <td><input type="number" id="totalLoadHrs"
                                        value="<?php echo array_sum(array_column($form_details, 'hrs_per_week')); ?>"
                                        class="small-input" readonly></td>
                            </tr>
                            <tr>
                                <td>CURRENTLY EMPLOYED IN OTHER GOV'T INSTITUTION</td>
                                <td class="checkbox-container">
                                    <input type="checkbox" id="yes" name="employed_elsewhere" value="1">
                                    <label for="yes" class="checkbox-label">YES</label>
                                    <input type="checkbox" id="no" name="not_employed_elsewhere" value="1" checked>
                                    <label for="no">NO</label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="button-container">
                        <div class="action-buttons">
                            <?php if ($_SESSION['user_type'] === 'Admin_Secretary'): ?>
                                <button class="btn btn-edit" id="edit-btn" onclick="toggleEditMode()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="confirmDelete()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button class="btn btn-save" id="save-btn" style="display: none;" onclick="saveChanges()">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button class="btn btn-cancel" id="cancel-btn" style="display: none;"
                                    onclick="toggleEditMode()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="right-buttons">
                            <button class="btn-export" onclick="exportForm()">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                    </div>

                    <form id="delete-form" method="POST" style="display: none;">
                        <input type="hidden" name="delete_form" value="1">
                        <input type="hidden" name="form_id"
                            value="<?php echo ($form_to_display ? $form_to_display['form_id'] : $current_form['form_id']); ?>">
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const contentArea = document.querySelector('.content-area');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const toggleIcon = document.getElementById('toggle-icon');


            sidebar.classList.toggle('active');
            contentArea.classList.toggle('sidebar-active');
            toggleBtn.classList.toggle('active');


            if (sidebar.classList.contains('active')) {
                toggleIcon.textContent = '❮';
            } else {
                toggleIcon.textContent = '❯';
            }
        }


        function initializeSidebar() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.removeEventListener('click', toggleSidebar);
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
        }


        document.addEventListener('DOMContentLoaded', initializeSidebar);


        window.goBackToPtDash = function() {
            console.log('Back button clicked - going to pt-dash.php');
            window.location.href = 'pt-dash.php';
        }


        document.addEventListener('DOMContentLoaded', initializeSidebar);

        function adjustTableLayout() {
            const table = document.getElementById('loading-table');
            if (!table) return;

            // Updated column widths including the new checkbox column
            const columnWidths = {
                0: '90px', // CODE
                1: '200px', // SUBJECT DESCRIPTION
                2: '75px', // LEC
                3: '75px', // LAB
                4: '120px', // HRS/WK
                5: '100px', // MON
                6: '100px', // TUE
                7: '100px', // WED
                8: '100px', // THU
                9: '100px', // FRI
                10: '100px', // SAT
                11: '100px', // SUN
                12: '80px', // ROOM
                13: '120px', // SECTION
                14: '80px', // PERIOD
            };

            Object.entries(columnWidths).forEach(([index, width]) => {
                const col = table.querySelectorAll(`th:nth-child(${parseInt(index)+1}), td:nth-child(${parseInt(index)+1})`);
                col.forEach(cell => {
                    cell.style.width = width;
                    cell.style.minWidth = width;
                    cell.style.maxWidth = width;
                });
            });
        }

        window.addEventListener('load', adjustTableLayout);
        window.addEventListener('resize', adjustTableLayout);

        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const formattedDate = now.toLocaleString('en-US', options);
            const dateElement = document.getElementById('date');
            if (dateElement) {
                dateElement.innerHTML = formattedDate;
            }
        }

        let originalTableHTML = '';

        function storeOriginalTableState() {
            const tbody = document.querySelector('#loading-table tbody');
            originalTableHTML = tbody.innerHTML;
        }


        function initializeEventListeners() {
            const backButton = document.querySelector('.content-area .back-button');
            if (backButton) {
                // Remove old listener to prevent duplicates if re-attaching
                backButton.removeEventListener('click', goBackToPtDash);
                backButton.addEventListener('click', goBackToPtDash);
            }

            const sidebarToggle = document.getElementById('sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.onclick = toggleSidebar;
            }

            const editBtn = document.getElementById('edit-btn');
            if (editBtn) {
                editBtn.addEventListener('click', toggleEditMode);
            }

            const addRowBtn = document.getElementById('add-row-btn');
            if (addRowBtn) {
                addRowBtn.addEventListener('click', addNewRow);
            }

            // Reattach subject code change handlers
            document.querySelectorAll('select.subject-code').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            });

            // Reattach subject type change handlers
            document.querySelectorAll('select[name="subject_type[]"]').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectType(this);
                });
            });

            // Reattach hour calculation handlers
            document.querySelectorAll('input[name="lec_hours[]"], input[name="lab_hours[]"]').forEach(input => {
                input.addEventListener('change', function() {
                    calculateHours(this);
                });
            });

            // Reattach day hour calculation handlers
            document.querySelectorAll('input[name^="monday"], input[name^="tuesday"], input[name^="wednesday"], input[name^="thursday"], input[name^="friday"], input[name^="saturday"], input[name^="sunday"]').forEach(input => {
                input.addEventListener('change', calculateDayHours);
            });

            adjustTableLayout();
            calculateTotals();
        }

        function viewSemester(semester) {
            const year = document.getElementById('year-dropdown').value;
            if (!year) {
                alert('Please select a school year first');
                return;
            }

            // Show loading indicator
            const contentArea = document.querySelector('.content-area');
            contentArea.innerHTML = '<div class="loading">Loading...</div>';

            // Update URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('school_year', year);
            urlParams.set('semester', semester);

            // Update browser URL without reloading
            history.pushState(null, '', `?${urlParams.toString()}`);

            // Include all necessary parameters in the fetch request
            fetch(`formload-PT.php?professor_id=${<?php echo $professor_id; ?>}&school_year=${year}&semester=${semester}&ajax=1`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    // Update the content area
                    contentArea.innerHTML = html;

                    // Reset edit mode if active
                    if (document.body.classList.contains('edit-mode')) {
                        document.body.classList.remove('edit-mode');
                    }

                    // Reinitialize all functions
                    initializeEssentialFunctions();
                    updateDateTime();
                    setInterval(updateDateTime, 1000);
                    adjustTableLayout();
                    calculateTotals();

                    // Update button states
                    const editBtn = document.getElementById('edit-btn');
                    const saveBtn = document.getElementById('save-btn');
                    const cancelBtn = document.getElementById('cancel-btn');
                    const addRowBtn = document.getElementById('add-row-btn');

                    if (editBtn) editBtn.style.display = 'flex';
                    if (saveBtn) saveBtn.style.display = 'none';
                    if (cancelBtn) cancelBtn.style.display = 'none';
                    if (addRowBtn) addRowBtn.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentArea.innerHTML = '<div class="error">Error loading content. Please try again.</div>';
                });
        }

        function initializeEssentialFunctions() {
            // Initialize back button
            const backButton = document.querySelector('.back-button');
            if (backButton) {
                backButton.onclick = goBackToPtDash;
            }

            // Initialize edit mode toggle
            const editBtn = document.getElementById('edit-btn');
            if (editBtn) {
                editBtn.onclick = toggleEditMode;
            }

            // Set up row selection in edit mode
            if (document.getElementById('loading-table').classList.contains('edit-mode')) {
                setupRowSelection();
            }

            // Initialize add row button
            const addRowBtn = document.getElementById('add-row-btn');
            if (addRowBtn) {
                addRowBtn.onclick = addNewRow;
            }

            document.querySelectorAll('select.subject-code').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            });


            // Initialize subject code dropdowns
            document.querySelectorAll('select[name="subject_type[]"]').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectType(this);
                });
            });

            // Initialize hour calculation handlers
            document.querySelectorAll('input[name="lec_hours[]"], input[name="lab_hours[]"]').forEach(input => {
                input.addEventListener('change', function() {
                    calculateHours(this);
                });
            });

            // Initialize day hour calculation handlers
            document.querySelectorAll('input[name^="monday"], input[name^="tuesday"], input[name^="wednesday"], input[name^="thursday"], input[name^="friday"], input[name^="saturday"], input[name^="sunday"]').forEach(input => {
                input.addEventListener('change', calculateDayHours);
            });

            // Calculate initial totals
            calculateTotals();
            adjustTableLayout();
        }

        function updateSemesterCards() {
            const year = document.getElementById('year-dropdown').value;
            if (!year) return;

            // Show loading state for all semester buttons
            const firstSemBtn = document.getElementById('first-sem-btn');
            const secondSemBtn = document.getElementById('second-sem-btn');
            const summerSemBtn = document.getElementById('summer-sem-btn');

            [firstSemBtn, secondSemBtn, summerSemBtn].forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
                btn.classList.add('disabled');
                btn.style.pointerEvents = 'none';
            });

            // Check data for all semesters
            Promise.all([
                checkSemesterData(year, '1st', firstSemBtn),
                checkSemesterData(year, '2nd', secondSemBtn),
                checkSemesterData(year, 'Summer', summerSemBtn)
            ]).then(() => {
                styleSemesterButtons();
            });
        }

        function styleSemesterButtons() {
            const firstSemBtn = document.getElementById('first-sem-btn');
            const secondSemBtn = document.getElementById('second-sem-btn');
            const summerSemBtn = document.getElementById('summer-sem-btn');

            [firstSemBtn, secondSemBtn, summerSemBtn].forEach(btn => {
                if (btn.classList.contains('has-data')) {
                    btn.classList.remove('disabled');
                    btn.style.pointerEvents = 'auto';
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                } else {
                    btn.classList.add('disabled');
                    btn.style.pointerEvents = 'none';
                }
            });
        }

        function checkSemesterData(year, semester, btnElement) {
            fetch(`../Backend/check_semester_data.php?professor_id=${<?php echo $professor_id; ?>}&school_year=${year}&semester=${semester}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.hasData) {
                        // Has data - enable button
                        btnElement.innerHTML = '<i class="fas fa-eye"></i> View';
                        btnElement.classList.remove('disabled');
                        btnElement.style.pointerEvents = 'auto';
                        btnElement.onclick = function() {
                            viewSemester(semester);
                        };
                    } else {
                        // No data - disable button
                        btnElement.innerHTML = '<i class="fas fa-eye-slash"></i> No Data';
                        btnElement.classList.add('disabled');
                        btnElement.style.pointerEvents = 'none';
                        btnElement.onclick = null;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btnElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                    btnElement.classList.add('disabled');
                });
        }

        function exportForm() {
            const templatePath = '../../fl_template.xlsx';

            // Get all the required data from the form
            const semesterSubtitle = document.getElementById('semester-subtitle').textContent;
            const name = document.querySelector('.info-item:nth-child(1) .value').textContent;
            const status = document.querySelector('.info-item:nth-child(2) .value').textContent;
            const period = document.getElementById('period-value').textContent;
            const collegeDepartment = document.querySelector('.info-item:nth-child(3) .value').textContent;

            // Get all subject rows (excluding disabled rows and the total row)
            const rows = Array.from(document.querySelectorAll('#loading-table tbody tr:not(:last-child)'))
                .filter(row => !row.classList.contains('row-disabled'));

            // Prepare subject data
            const subjectData = rows.map(row => {
                return {
                    code: row.querySelector('input[name="subject_code[]"], select[name="subject_code[]"]')?.value || '',
                    description: row.querySelector('.subject-description-combined, .subject-description-base')?.value || '',
                    lec: row.querySelector('input[name="lec_hours[]"]')?.value || '0',
                    lab: row.querySelector('input[name="lab_hours[]"]')?.value || '0',
                    hrsPerWeek: row.querySelector('input[name="hrs_per_week[]"]')?.value || '0',
                    mon: row.querySelector('input[name="monday[]"]')?.value || '',
                    tue: row.querySelector('input[name="tuesday[]"]')?.value || '',
                    wed: row.querySelector('input[name="wednesday[]"]')?.value || '',
                    thu: row.querySelector('input[name="thursday[]"]')?.value || '',
                    fri: row.querySelector('input[name="friday[]"]')?.value || '',
                    sat: row.querySelector('input[name="saturday[]"]')?.value || '',
                    sun: row.querySelector('input[name="sunday[]"]')?.value || '',
                    room: row.querySelector('input[name="room[]"]')?.value || '',
                    section: row.querySelector('input[name="section[]"], select[name="section[]"]')?.value || '',
                    period: row.querySelector('input[name="period_col[]"]')?.value || ''
                };
            });

            // Get totals and other calculations
            const totalLec = document.getElementById('totalLec').textContent;
            const totalLab = document.getElementById('totalLab').textContent;
            const totalHrsWk = document.getElementById('totalHrsWk').textContent;
            const numPreparations = document.getElementById('numPreparations').value;
            const lowestHrs = document.getElementById('lowestHrs').value;
            const highestHrs = document.getElementById('highestHrs').value;
            const totalLoadUnits = document.getElementById('totalLoadUnits').value;
            const totalLoadHrs = document.getElementById('totalLoadHrs').value;

            // Prepare the data to send to the server
            const exportData = {
                templatePath: templatePath,
                data: {
                    semesterSubtitle: semesterSubtitle,
                    name: name,
                    status: status,
                    period: period,
                    collegeDepartment: collegeDepartment,
                    subjects: subjectData,
                    totals: {
                        lec: totalLec,
                        lab: totalLab,
                        hrsWk: totalHrsWk
                    },
                    calculations: {
                        numPreparations: numPreparations,
                        lowestHrs: lowestHrs,
                        highestHrs: highestHrs,
                        totalLoadUnits: totalLoadUnits,
                        totalLoadHrs: totalLoadHrs
                    }
                }
            };

            // Show loading state
            const exportBtn = document.querySelector('.btn-export');
            const originalBtnContent = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            exportBtn.disabled = true;

            // Send data to server for Excel generation
            fetch('../Backend/Exporting/export_formload.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(exportData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Faculty_Loading_PT_${name.replace(/\s+/g, '_')}_${semesterSubtitle.replace(/\s+/g, '_')}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error exporting to Excel: ' + error.message);
                })
                .finally(() => {
                    // Restore button state
                    exportBtn.innerHTML = originalBtnContent;
                    exportBtn.disabled = false;
                });
        }

        function toggleRowDisabledState() {
            if (!selectedRow) {
                alert('Please select a row first by clicking on it.');
                return;
            }

            const disableBtn = document.getElementById('disable-row-btn');
            if (!disableBtn) return;

            const btnIcon = disableBtn.querySelector('i');
            const btnText = disableBtn.querySelector('.btn-text');

            if (selectedRow.classList.contains('row-disabled')) {
                // Enable the row
                selectedRow.classList.remove('row-disabled');
                selectedRow.querySelectorAll('input, select').forEach(input => {
                    input.disabled = false;
                });

                if (btnIcon) btnIcon.className = 'fas fa-ban';
                if (btnText) btnText.textContent = 'Disable Row';
                disableBtn.classList.remove('enable-row-btn');
            } else {
                // Disable the row
                selectedRow.classList.add('row-disabled');
                selectedRow.querySelectorAll('input, select').forEach(input => {
                    input.disabled = true;
                });

                if (btnIcon) btnIcon.className = 'fas fa-check';
                if (btnText) btnText.textContent = 'Enable Row';
                disableBtn.classList.add('enable-row-btn');
            }

            // Recalculate totals after changing row state
            calculateTotals();
        }

        function viewCurrentLoading() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('active')) {
                toggleSidebar(); // Close the sidebar
            }

            const contentArea = document.querySelector('.content-area');
            contentArea.innerHTML = '<div class="loading">Loading...</div>';

            fetch(`formload.php?professor_id=${<?php echo json_encode($professor_id); ?>}&ajax=1`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    contentArea.innerHTML = html;

                    const currentForm = <?php echo json_encode($current_form); ?>;
                    if (currentForm) {
                        document.getElementById('semester-subtitle').textContent =
                            `${currentForm.semester.toUpperCase()} SEMESTER A.Y ${currentForm.school_year}`;
                        document.getElementById('period-value').textContent = currentForm.period;
                    }

                    const backButton = document.querySelector('.back-button');
                    if (backButton) {
                        backButton.onclick = function(e) {
                            e.preventDefault();
                            goBackToPtDash();
                        };
                    }

                    // Reinitialize the sidebar toggle
                    const sidebarToggle = document.getElementById('sidebar-toggle');
                    if (sidebarToggle) {
                        sidebarToggle.onclick = toggleSidebar;
                    }

                    initializeEventListeners();
                    calculateTotals();
                    adjustTableLayout();
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentArea.innerHTML = '<div class="error">Error loading content. Please try again.</div>';
                });
        }

        let selectedRow = null;

        function setupRowSelection() {
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');

            rows.forEach(row => {
                const newRow = row.cloneNode(true);
                row.parentNode.replaceChild(newRow, row);

                newRow.addEventListener('click', function() {
                    if (selectedRow) {
                        selectedRow.classList.remove('row-selected');
                    }

                    if (!this.classList.contains('totals-row')) {
                        this.classList.add('row-selected');
                        selectedRow = this;

                        const disableBtn = document.getElementById('disable-row-btn');
                        const btnIcon = disableBtn.querySelector('i');
                        const btnText = disableBtn.querySelector('.btn-text');

                        if (this.classList.contains('row-disabled')) {
                            btnIcon.className = 'fas fa-check';
                            btnText.textContent = 'Enable Row';
                            disableBtn.classList.add('enable-row-btn');
                        } else {
                            btnIcon.className = 'fas fa-ban';
                            btnText.textContent = 'Disable Row';
                            disableBtn.classList.remove('enable-row-btn');
                        }
                    } else {
                        selectedRow = null;
                    }
                });
            });
        }

        function deleteSelectedRow() {
            if (!selectedRow) {
                alert('Please select a row to delete by clicking on it.');
                return;
            }

            if (confirm('Are you sure you want to delete this row?')) {
                selectedRow.remove();
                selectedRow = null;
                calculateTotals();
                adjustTableLayout();
            }
        }

        function toggleEditMode() {
            console.log('Toggle edit mode called');
            const table = document.getElementById('loading-table');
            console.log('Table classes:', table.classList);

            const editBtn = document.getElementById('edit-btn');
            const saveBtn = document.getElementById('save-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const addRowBtn = document.getElementById('add-row-btn');
            const deleteRowBtn = document.getElementById('delete-row-btn');
            const disableRowBtn = document.getElementById('disable-row-btn');

            if (!editBtn || !saveBtn || !cancelBtn || !addRowBtn || !deleteRowBtn || !disableRowBtn) {
                console.error('One or more buttons not found');
                return;
            }

            if (table.classList.contains('edit-mode')) {
                // Exit edit mode
                const tbody = document.querySelector('#loading-table tbody');
                tbody.innerHTML = originalTableHTML;

                table.classList.remove('edit-mode');
                editBtn.style.display = 'flex';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                addRowBtn.style.display = 'none';
                deleteRowBtn.style.display = 'none';
                disableRowBtn.style.display = 'none';

                // Safely update disable button state
                const btnIcon = disableRowBtn.querySelector('i');
                const btnText = disableRowBtn.querySelector('.btn-text');

                if (btnIcon) btnIcon.className = 'fas fa-ban';
                if (btnText) btnText.textContent = 'Disable Row';
                disableRowBtn.classList.remove('enable-row-btn');

                // Hide all subject type dropdowns
                document.querySelectorAll('.subject-type-toggle').forEach(toggle => {
                    toggle.style.display = 'none';
                });

                // Convert back to readonly inputs
                convertToReadonly();
                initializeEssentialFunctions();
            } else {
                // Enter edit mode
                storeOriginalTableState();

                table.classList.add('edit-mode');
                editBtn.style.display = 'none';
                saveBtn.style.display = 'flex';
                cancelBtn.style.display = 'flex';
                addRowBtn.style.display = 'block';
                deleteRowBtn.style.display = 'block';
                disableRowBtn.style.display = 'block';

                document.querySelectorAll('.subject-type-toggle').forEach(toggle => {
                    toggle.style.display = 'none';
                });

                convertToEditable();

                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                    selectedRow = null;
                }

                // Safely update disable button state
                const btnIcon = disableRowBtn.querySelector('i');
                const btnText = disableRowBtn.querySelector('.btn-text');

                if (btnIcon) btnIcon.className = 'fas fa-ban';
                if (btnText) btnText.textContent = 'Disable Row';
                disableRowBtn.classList.remove('enable-row-btn');

                setupRowSelection();
            }

            calculateTotals();
            setTimeout(adjustTableLayout, 0);
        }

        function convertToEditable() {
            const periodValue = document.getElementById('period-value').textContent;
            const subjectTypeToggles = document.querySelectorAll('.subject-type-toggle');

            document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                input.value = periodValue;
                input.readOnly = true;
            });
            document.querySelectorAll('select[name="subject_type[]"]').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectType(this);
                });
            });

            document.querySelectorAll('input[name="section[]"]').forEach(input => {
                const select = document.createElement('select');
                select.name = input.name;
                select.className = input.className;
                select.innerHTML = `
            <option value="" hidden>Select Section</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?php echo htmlspecialchars($section['section_name']); ?>"
                        ${input.value === '<?php echo htmlspecialchars($section['section_name']); ?>' ? 'selected' : ''}>
                    <?php echo htmlspecialchars($section['section_name']); ?>
                </option>
            <?php endforeach; ?>
        `;
                input.parentNode.replaceChild(select, input);
            });

            document.querySelectorAll('input.subject-code').forEach(input => {
                const select = document.createElement('select');
                select.name = input.name;
                select.className = input.className + ' editable-input';
                select.innerHTML = `
                    <option value="" hidden>Select Subject</option>
                        <?php foreach ($subjects as $subject) : ?>
                            <option value="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                data-description="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                <?php echo ($detail['subject_code'] == $subject['subject_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                            </option>
                        <?php endforeach; ?>
                `;
                input.parentNode.replaceChild(select, input);
                select.addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            });

            // Handle subject descriptions
            document.querySelectorAll('.subject-description-combined').forEach(combinedInput => {
                const combinedValue = combinedInput.value;
                let baseDescription = combinedValue.replace(/\s*\(LEC\)|\s*\(LAB\)/, '').trim();
                let type = 'LEC';

                if (combinedValue.includes('(LAB)')) {
                    type = 'LAB';
                }

                const container = document.createElement('div');
                container.className = 'subject-desc-container';
                container.style.display = 'flex';
                container.style.width = '100%';

                const inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.name = 'subject_description_base[]';
                inputField.className = 'subject-description-base';
                inputField.value = baseDescription;
                inputField.style.flex = '1';
                inputField.style.marginRight = '5px';

                const typeDropdown = document.createElement('select');
                typeDropdown.name = 'subject_type[]';
                typeDropdown.className = 'subject-type-toggle';
                typeDropdown.style.width = '80px';
                typeDropdown.innerHTML = `
                    <option value="(LEC)" ${type === 'LEC' ? 'selected' : ''}>(LEC)</option>
                    <option value="(LAB)" ${type === 'LAB' ? 'selected' : ''}>(LAB)</option>
                    <option value="" ${type === '' ? 'selected' : ''}>None</option>
                `;

                inputField.addEventListener('input', function() {
                    updateSubjectType(typeDropdown);
                });

                typeDropdown.addEventListener('change', function() {
                    updateSubjectType(this);
                });

                container.appendChild(inputField);
                container.appendChild(typeDropdown);

                const parentElement = combinedInput.parentNode;
                parentElement.replaceChild(container, combinedInput);
            });

            document.querySelectorAll('#loading-table tbody input').forEach(input => {
                if (input.name !== 'period_col[]') {
                    input.readOnly = false;
                }
                if (input.name.includes('lec_hours[]') || input.name.includes('lab_hours[]')) {
                    input.addEventListener('change', function() {
                        calculateHours(this);
                    });
                }
            });

            document.querySelectorAll('input[name^="monday"], input[name^="tuesday"], input[name^="wednesday"], input[name^="thursday"], input[name^="friday"], input[name^="saturday"], input[name^="sunday"]').forEach(input => {
                input.addEventListener('change', calculateDayHours);
            });

        }

        function convertToReadonly() {
            const periodValue = document.getElementById('period-value').textContent;

            // Convert subject code selects back to readonly inputs
            document.querySelectorAll('select.subject-code').forEach(select => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = select.name;
                input.className = 'subject-code';
                input.value = select.value;
                input.readOnly = true;
                select.parentNode.replaceChild(input, select);
            });

            // Make subject description base readonly
            document.querySelectorAll('.subject-description-base').forEach(input => {
                input.readOnly = true;
            });

            // Disable subject type dropdown
            document.querySelectorAll('.subject-type-toggle').forEach(toggle => {
                toggle.style.display = 'none';
            });

            // Convert section dropdowns back to readonly inputs
            document.querySelectorAll('select[name="section[]"]').forEach(select => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = select.name;
                input.value = select.value;
                input.readOnly = true;
                select.parentNode.replaceChild(input, select);
            });

            // Make all inputs readonly (except period column)
            document.querySelectorAll('#loading-table tbody input').forEach(input => {
                if (input.name !== 'period_col[]') {
                    input.readOnly = true;
                }
            });

            // Hide subject type toggles
            document.querySelectorAll('.subject-type-toggle').forEach(toggle => {
                toggle.style.display = 'none';
                toggle.disabled = true;
            });

            // Ensure period column has the correct value and is readonly
            document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                input.value = periodValue;
                input.readOnly = true;
            });
        }

        // Store original values before editing
        let originalValues = [];

        function storeOriginalValues() {
            originalValues = [];
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');
            rows.forEach(row => {
                const rowData = {};
                row.querySelectorAll('input, select').forEach(input => {
                    rowData[input.name] = input.value;
                });
                originalValues.push(rowData);
            });
        }

        function restoreOriginalValues() {
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');
            rows.forEach((row, index) => {
                if (originalValues[index]) {
                    Object.entries(originalValues[index]).forEach(([name, value]) => {
                        const input = row.querySelector(`[name="${name}"]`);
                        if (input) {
                            input.value = value;
                        }
                    });
                }
            });
            calculateTotals();
        }

        // Update subject type (LEC/LAB/NONE) and adjust hours
        function updateSubjectType(select) {
            const row = select.closest('tr');
            const descriptionInput = row.querySelector('.subject-description-base');
            const combinedInput = row.querySelector('.subject-description-combined');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');
            const hrsPerWeekInput = row.querySelector('input[name="hrs_per_week[]"]');

            const type = select.value;

            if (descriptionInput && descriptionInput.value) {
                if (combinedInput) {
                    let baseDesc = descriptionInput.value.replace(/\s*\(LEC\)|\s*\(LAB\)/g, '').trim();
                    combinedInput.value = baseDesc + (type ? ` (${type})` : '');
                }
            }

            // Update hours based on type
            if (type === 'LEC') {
                lecInput.value = '2';
                labInput.value = '0';
                hrsPerWeekInput.value = '2';
            } else if (type === 'LAB') {
                lecInput.value = '0';
                labInput.value = '1';
                hrsPerWeekInput.value = '3';
            } else {
                // For NONE option
                lecInput.value = '0';
                labInput.value = '1';
                hrsPerWeekInput.value = '3';
            }

            calculateTotals();
        }

        // Add event listeners for subject type changes
        document.querySelectorAll('select[name="subject_type[]"]').forEach(select => {
            select.addEventListener('change', function() {
                updateSubjectType(this);
            });
        });

        function updateSubjectDescription(select) {
            const row = select.closest('tr');
            const descriptionInput = row.querySelector('.subject-description-base');
            const typeSelect = row.querySelector('select[name="subject_type[]"]');
            const combinedInput = row.querySelector('.subject-description-combined');

            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.dataset.description) {
                const baseDescription = selectedOption.dataset.description;

                if (descriptionInput) {
                    descriptionInput.readOnly = false;
                    descriptionInput.value = baseDescription;
                    descriptionInput.readOnly = true;
                }

                if (combinedInput) {
                    const type = typeSelect ? typeSelect.value : 'LEC';
                    combinedInput.value = baseDescription + (type ? ` (${type})` : '');
                }
            }

            if (typeSelect) {
                updateSubjectType(typeSelect);
            }
        }

        function calculateHours(input) {
            const row = input.closest('tr');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');
            const hrsPerWeekInput = row.querySelector('input[name="hrs_per_week[]"]');

            const lecHours = parseFloat(lecInput.value) || 0;
            const labHours = parseFloat(labInput.value) || 0;

            let totalHrs = lecHours;

            if (labHours === 1) {
                totalHrs += 3;
            } else if (labHours === 2) {
                totalHrs += 2;
            } else if (labHours > 0) {
                totalHrs += labHours;
            }

            hrsPerWeekInput.value = totalHrs;
            calculateTotals();
        }

        function calculateTotals() {
            let totalLec = 0;
            let totalLab = 0;
            let totalHrsWk = 0;
            let totalLoadUnits = 0;
            const uniqueSubjects = new Set();

            // Check if table exists
            const table = document.getElementById('loading-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr:not(:last-child)');

            rows.forEach(row => {
                // Skip disabled rows
                if (row.classList.contains('row-disabled')) {
                    return;
                }

                const lecInput = row.querySelector('input[name="lec_hours[]"]');
                const labInput = row.querySelector('input[name="lab_hours[]"]');
                const hrsInput = row.querySelector('input[name="hrs_per_week[]"]');

                const lecValue = lecInput ? parseFloat(lecInput.value) || 0 : 0;
                const labValue = labInput ? parseFloat(labInput.value) || 0 : 0;
                const hrsValue = hrsInput ? parseFloat(hrsInput.value) || 0 : 0;

                // Get subject code
                let subjectCode = '';
                const subjectCodeInput = row.querySelector('input[name="subject_code[]"]');
                const subjectCodeSelect = row.querySelector('select[name="subject_code[]"]');

                if (subjectCodeSelect) {
                    subjectCode = subjectCodeSelect.value;
                } else if (subjectCodeInput) {
                    subjectCode = subjectCodeInput.value;
                }

                totalLec += lecValue;
                totalLab += labValue;
                totalHrsWk += hrsValue;

                if (subjectCode) {
                    uniqueSubjects.add(subjectCode);
                }
            });

            const totalLecElement = document.getElementById('totalLec');
            const totalLabElement = document.getElementById('totalLab');
            const totalHrsWkElement = document.getElementById('totalHrsWk');
            const numPreparationsElement = document.getElementById('numPreparations');
            const totalLoadHrsElement = document.getElementById('totalLoadHrs');
            const totalLoadUnitsElement = document.getElementById('totalLoadUnits');

            if (totalLecElement) totalLecElement.textContent = totalLec;
            if (totalLabElement) totalLabElement.textContent = totalLab;
            if (totalHrsWkElement) totalHrsWkElement.textContent = totalHrsWk;
            if (numPreparationsElement) numPreparationsElement.value = uniqueSubjects.size;
            if (totalLoadHrsElement) totalLoadHrsElement.value = totalHrsWk;

            if (totalLoadUnitsElement) {
                // Calculate totalLoadUnits as the sum of totalLec and totalLab
                totalLoadUnitsElement.value = (parseFloat(totalLec) + parseFloat(totalLab));
            }

            calculateDayHours();
        }

        function calculateDayHours() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            const dayHours = {};

            days.forEach(day => {
                dayHours[day] = 0;
            });

            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');
            rows.forEach(row => {
                if (row.classList.contains('row-disabled')) {
                    return;
                }

                const hrsValue = parseFloat(row.querySelector('input[name="hrs_per_week[]"]').value) || 0;

                days.forEach(day => {
                    const dayInput = row.querySelector(`input[name="${day}[]"]`);
                    if (dayInput && dayInput.value) {
                        dayHours[day] += hrsValue;
                    }
                });
            });

            const nonZeroDays = Object.entries(dayHours).filter(([day, hours]) => hours > 0);

            if (nonZeroDays.length > 0) {
                const sortedDays = nonZeroDays.sort((a, b) => a[1] - b[1]);
                document.getElementById('lowestHrs').value = sortedDays[0][1];
                document.getElementById('highestHrs').value = sortedDays[sortedDays.length - 1][1];
            } else {
                document.getElementById('lowestHrs').value = 0;
                document.getElementById('highestHrs').value = 0;
            }
        }

        function calculateTotalLoadUnits() {
            const subjects = <?php echo json_encode($subjects); ?>;
            const subjectUnitsMap = {};

            // Create a map of subject codes to units
            subjects.forEach(subject => {
                subjectUnitsMap[subject.subject_code] = subject.units;
            });

            const uniqueSubjects = new Set();
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');

            rows.forEach(row => {
                // Skip disabled rows
                if (row.classList.contains('row-disabled')) {
                    return;
                }

                const subjectCodeInput = row.querySelector('input[name="subject_code[]"]');
                const subjectCodeSelect = row.querySelector('select[name="subject_code[]"]');

                const subjectCode = subjectCodeSelect ? subjectCodeSelect.value :
                    (subjectCodeInput ? subjectCodeInput.value : '');

                if (subjectCode) {
                    uniqueSubjects.add(subjectCode);
                }
            });

            let totalUnits = 0;
            uniqueSubjects.forEach(code => {
                if (subjectUnitsMap[code]) {
                    totalUnits += parseFloat(subjectUnitsMap[code]);
                }
            });

            return totalUnits;
        }

        function calculateHours(input) {
            const row = input.closest('tr');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');
            const hrsPerWeekInput = row.querySelector('input[name="hrs_per_week[]"]');

            const lecHours = parseFloat(lecInput.value) || 0;
            const labHours = parseFloat(labInput.value) || 0;

            let totalHrs = lecHours;

            if (labHours === 1) {
                totalHrs += 3;
            } else if (labHours === 2) {
                totalHrs += 2;
            } else if (labHours > 0) {
                totalHrs += labHours;
            }

            hrsPerWeekInput.value = totalHrs;
            calculateTotals();
        }

        let newlyAddedRows = [];

        function addNewRow() {
            const table = document.getElementById('loading-table');
            const tbody = table.querySelector('tbody');
            const lastRow = tbody.querySelector('tr:last-child');
            const firstRow = tbody.querySelector('tr');

            // Create new row
            const newRow = firstRow.cloneNode(true);
            newRow.classList.add('new-row');

            // Clear all values in the new row
            const inputs = newRow.querySelectorAll('input');
            inputs.forEach(input => {
                if (!input.readOnly && !input.classList.contains('row-checkbox')) {
                    input.value = '';
                    if (input.name === 'lec_hours[]') {
                        input.value = '2';
                    } else if (input.name === 'lab_hours[]') {
                        input.value = '0';
                    } else if (input.name === 'hrs_per_week[]') {
                        input.value = '2';
                    }
                }
            });

            // Update subject type select options
            const typeSelect = newRow.querySelector('select[name="subject_type[]"]');
            if (typeSelect) {
                typeSelect.innerHTML = `
            <option value="LEC" selected>LEC</option>
            <option value="LAB">LAB</option>
            <option value="">None</option>
        `;
            }

            // Reset select values
            const selects = newRow.querySelectorAll('select');
            selects.forEach(select => {
                if (select.name === 'subject_code[]') {
                    select.selectedIndex = 0;
                } else if (select.name === 'section[]') {
                    select.selectedIndex = 0;
                }
            });

            document.querySelectorAll('select.subject-code').forEach(select => {
                select.addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            });

            // Add event listeners for the new row
            if (newRow.querySelector('select[name="subject_code[]"]')) {
                newRow.querySelector('select[name="subject_code[]"]').addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            }

            if (newRow.querySelector('select[name="subject_type[]"]')) {
                newRow.querySelector('select[name="subject_type[]"]').addEventListener('change', function() {
                    updateSubjectType(this);
                });
            }

            const lecLabInputs = newRow.querySelectorAll('input[name="lec_hours[]"], input[name="lab_hours[]"]');
            lecLabInputs.forEach(input => {
                input.addEventListener('change', function() {
                    calculateHours(this);
                });
            });

            const dayInputs = newRow.querySelectorAll('input[name^="monday"], input[name^="tuesday"], input[name^="wednesday"], input[name^="thursday"], input[name^="friday"], input[name^="saturday"], input[name^="sunday"]');
            dayInputs.forEach(input => {
                input.addEventListener('change', calculateDayHours);
            });

            // Add click event listener for row selection
            newRow.addEventListener('click', function() {
                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                }

                if (!this.classList.contains('totals-row')) {
                    this.classList.add('row-selected');
                    selectedRow = this;

                    const disableBtn = document.getElementById('disable-row-btn');
                    const btnIcon = disableBtn.querySelector('i');
                    const btnText = disableBtn.querySelector('.btn-text');

                    if (this.classList.contains('row-disabled')) {
                        btnIcon.className = 'fas fa-check';
                        btnText.textContent = 'Enable Row';
                        disableBtn.classList.add('enable-row-btn');
                    } else {
                        btnIcon.className = 'fas fa-ban';
                        btnText.textContent = 'Disable Row';
                        disableBtn.classList.remove('enable-row-btn');
                    }
                } else {
                    selectedRow = null;
                }
            });

            tbody.insertBefore(newRow, lastRow);
            newlyAddedRows.push(newRow);

            newRow.click();

            setTimeout(adjustTableLayout, 0);
        }

        function validateBeforeSave() {
            let isValid = true;
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');
            const errorMessages = [];

            rows.forEach((row, index) => {
                const subjectCodeSelect = row.querySelector('select[name="subject_code[]"]');
                const subjectCode = subjectCodeSelect ? subjectCodeSelect.value : row.querySelector('input[name="subject_code[]"]').value;

                if (!subjectCode) {
                    errorMessages.push(`Row ${index + 1}: Subject code is required`);
                    row.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                    isValid = false;
                } else {
                    row.style.backgroundColor = '';
                }
            });

            if (!isValid) {
                alert('Validation errors:\n' + errorMessages.join('\n'));
            }
            return isValid;
        }

        function saveChanges() {
            console.log('saveChanges() called - Starting save process');

            const subtitleText = document.querySelector('.form-subtitle').textContent.trim();
            const semesterMatch = subtitleText.match(/^(\w+)/);
            const yearMatch = subtitleText.match(/A\.Y\s(.+)$/);

            if (!semesterMatch || !yearMatch) {
                alert('Error: Could not determine current semester/year from page');
                return;
            }

            // Get current semester/year
            const schoolYear = yearMatch[1].trim();
            const semester = semesterMatch[1].trim().toLowerCase();

            // Validate form before proceeding
            if (!validateBeforeSave()) {
                console.log('Validation failed - Form contains errors');
                return;
            }

            if (!validateNumericFields()) {
                console.log('Validation failed - Numeric fields contain invalid values');
                return;
            }

            const saveBtn = document.getElementById('save-btn');
            if (!saveBtn) {
                console.error('Critical error: Save button not found');
                return;
            }

            // Get all required form data from the page
            const formId = <?php echo json_encode(($form_to_display ? $form_to_display['form_id'] : ($current_form ? $current_form['form_id'] : null))); ?>;
            const professorId = <?php echo json_encode((string)$professor_id); ?>;
            const periodValue = document.getElementById('period-value').textContent.trim();
            const collegeDept = document.querySelector('.info-item:nth-child(3) .value').textContent.trim();

            // Prepare for save operation
            const originalBtnText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;

            const formData = new FormData();

            // Add basic form information
            formData.append('form_id', formId !== null ? formId.toString() : '');
            formData.append('professor_id', professorId);
            formData.append('school_year', schoolYear);
            formData.append('semester', semester);
            formData.append('period', periodValue);
            formData.append('college_department', collegeDept);

            // Add row data
            const rows = document.querySelectorAll('#loading-table.edit-mode tbody tr:not(:last-child)');
            console.log(`Saving ${rows.length} rows of data`);

            rows.forEach((row, index) => {
                const disabled = row.classList.contains('row-disabled') ? '1' : '0';
                const detailId = row.getAttribute('data-detail-id') || '';

                // Get subject code
                const subjectCodeSelect = row.querySelector('select[name="subject_code[]"]');
                const subjectCodeInput = row.querySelector('input[name="subject_code[]"]');
                const subjectCode = subjectCodeSelect ? subjectCodeSelect.value.trim() :
                    (subjectCodeInput ? subjectCodeInput.value.trim() : '');

                // Get subject description and type
                const baseDescInput = row.querySelector('input[name="subject_description_base[]"]');
                const typeSelect = row.querySelector('select[name="subject_type[]"]');

                const baseDesc = baseDescInput ? baseDescInput.value.trim() : '';
                const type = typeSelect ? typeSelect.value : 'LEC';

                // Get hours (with proper decimal handling)
                const lecValue = (row.querySelector('input[name="lec_hours[]"]')?.value || '0').replace(',', '.');
                const labValue = (row.querySelector('input[name="lab_hours[]"]')?.value || '0').replace(',', '.');
                const hrsValue = (row.querySelector('input[name="hrs_per_week[]"]')?.value || '0').replace(',', '.');

                // Get days
                const days = {};
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(day => {
                    const dayInput = row.querySelector(`input[name="${day}[]"]`);
                    days[day] = dayInput ? dayInput.value.trim() : '';
                });

                // Get room and section
                const roomInput = row.querySelector('input[name="room[]"]');
                const room = roomInput ? roomInput.value.trim() : '';

                const sectionSelect = row.querySelector('select[name="section[]"]');
                const sectionInput = row.querySelector('input[name="section[]"]');
                const section = sectionSelect ? sectionSelect.value : (sectionInput ? sectionInput.value.trim() : '');

                // Append all row data to formData
                formData.append(`detail_ids[${index}]`, detailId);
                formData.append(`subject_codes[${index}]`, subjectCode);
                formData.append(`subject_base_description[${index}]`, baseDesc);
                formData.append(`subject_type[${index}]`, type);
                formData.append(`lec_hours[${index}]`, lecValue);
                formData.append(`lab_hours[${index}]`, labValue);
                formData.append(`hrs_per_week[${index}]`, hrsValue);
                formData.append(`disabled_rows[${index}]`, disabled);
                formData.append(`room[${index}]`, room);
                formData.append(`section[${index}]`, section);

                Object.keys(days).forEach(day => {
                    formData.append(`${day}[${index}]`, days[day]);
                });
            });

            // Send the data to server
            fetch('../Backend/save_formload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        newlyAddedRows = [];
                        console.log('Save successful:', data.message);
                        alert('Changes saved successfully!');

                        const urlParams = new URLSearchParams(window.location.search);
                        urlParams.set('school_year', schoolYear);
                        urlParams.set('semester', semester);

                        // Use AJAX to reload just the content area
                        fetch(`formload.php?professor_id=${professorId}&school_year=${schoolYear}&semester=${semester}&ajax=1`)
                            .then(response => response.text())
                            .then(html => {
                                document.querySelector('.content-area').innerHTML = html;
                                initializeEssentialFunctions();
                                calculateTotals();
                                adjustTableLayout();

                                // Exit edit mode
                                if (document.body.classList.contains('edit-mode')) {
                                    toggleEditMode();
                                }
                            });
                    } else {
                        console.error('Save failed:', data.message);
                        throw new Error(data.message || 'Unknown error occurred while saving');
                    }
                })
                .catch(error => {
                    console.error('Error during save:', error);
                    alert(`Error saving changes: ${error.message}`);
                })
                .finally(() => {
                    saveBtn.innerHTML = originalBtnText;
                    saveBtn.disabled = false;
                    console.log('Save process completed');
                });
        }

        function validateNumericFields() {
            let isValid = true;
            const rows = document.querySelectorAll('#loading-table tbody tr:not(:last-child)');

            rows.forEach((row, index) => {
                const lecInput = row.querySelector('input[name="lec_hours[]"]');
                const labInput = row.querySelector('input[name="lab_hours[]"]');
                const hrsInput = row.querySelector('input[name="hrs_per_week[]"]');

                // Validate lec_hours
                if (lecInput && !isValidNumber(lecInput.value)) {
                    alert(`Invalid lecture hours value in row ${index + 1}. Please enter a valid number.`);
                    lecInput.focus();
                    isValid = false;
                    return;
                }

                // Validate lab_hours
                if (labInput && !isValidNumber(labInput.value)) {
                    alert(`Invalid lab hours value in row ${index + 1}. Please enter a valid number.`);
                    labInput.focus();
                    isValid = false;
                    return;
                }

                // Validate hrs_per_week
                if (hrsInput && !isValidNumber(hrsInput.value)) {
                    alert(`Invalid hours per week value in row ${index + 1}. Please enter a valid number.`);
                    hrsInput.focus();
                    isValid = false;
                    return;
                }
            });

            return isValid;
        }

        function isValidNumber(value) {
            // Allow empty string (will be converted to 0 on server)
            if (value === '') return true;

            // Check if it's a valid number (after replacing commas with dots)
            const numericValue = value.replace(',', '.');
            return !isNaN(numericValue) && !isNaN(parseFloat(numericValue));
        }

        function getFieldName(colIndex) {
            // Map column index to database field names
            const fieldMap = {
                0: 'subject_code',
                1: 'subject_description',
                2: 'lec_hours',
                3: 'lab_hours',
                4: 'hrs_per_week',
                5: 'monday',
                6: 'tuesday',
                7: 'wednesday',
                8: 'thursday',
                9: 'friday',
                10: 'saturday',
                11: 'sunday',
                12: 'room',
                13: 'section',
                14: 'period'
            };
            return fieldMap[colIndex] || null;
        }

        function confirmDelete() {
            if (confirm('Are you sure you want to delete this loading form?')) {
                const contentArea = document.querySelector('.content-area');
                contentArea.innerHTML = '<div class="loading">Deleting form...</div>';

                const professorId = <?php echo json_encode($professor_id); ?>;
                const formId = <?php echo json_encode(($form_to_display ? $form_to_display['form_id'] : ($current_form ? $current_form['form_id'] : ''))); ?>;

                fetch('../Backend/delete_form.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `form_id=${formId}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Show success message and redirect
                            contentArea.innerHTML = '<div class="loading" style="color: green;">Successful delete... redirecting you to Form Loading page.</div>';
                            setTimeout(() => {
                                window.location.href = `emp-formload.php?deleted=1`;
                            }, 2000); // Redirect after 2 seconds
                        } else {
                            throw new Error(data.message || 'Failed to delete form');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        contentArea.innerHTML = '<div class="error">Error deleting form: ' + error.message + '</div>';
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    });
            }
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebar();
            initializeEssentialFunctions();
            updateDateTime();
            setInterval(updateDateTime, 1000);
            adjustTableLayout();
            calculateTotals();

            // Initialize sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }

            // Initialize back button
            const backButton = document.querySelector('.back-button');
            if (backButton) {
                backButton.addEventListener('click', goBackToPtDash);
            }

            const yearDropdown = document.getElementById('year-dropdown');
            const selectedYear = yearDropdown ? yearDropdown.value : null;
            const selectedSemester = <?php echo json_encode($selected_semester); ?>;

            if (selectedYear && selectedSemester && <?php echo ($form_to_display ? 'false' : 'true'); ?>) {
                alert('No form loading data available for the selected school year and semester.');
            }
        });
    </script>
</body>

</html>