<!DOCTYPE html>
<html>

<head>
    <title>Pareģošana</title>
    <link rel="stylesheet" href="style.css">
    <!-- Include flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <div class="container">
        <h1>Šķeldas pateriņa kalkulators</h1>
        <form action="calculate.php" method="post">
            <label for="location">Izvēlies KM:</label>
            <select id="location" name="location">
                <option value="">-- Izvēlies --</option>
                <option value="Bauskas 207A">Bauskas 207A</option>
                <option value="Nautrēnu 24">Nautrēnu 24</option>
            </select>
            <label for="woodchip_m3">Šķeldas daudzums (m<sup>3</sup>):</label>
            <input type="number" id="woodchip_m3" name="woodchip_m3" min="0" step="0.01" required>
            <div class="date-time-group">
                <div>
                    <label for="start_date">Sākuma datums:</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="start_date" name="start_date" class="flatpickr-input flatpickr-date" required
                        readonly="readonly">
                    <input type="hidden" name="start_date">
                </div>
                <div>
                    <label for="start_time">Sākuma laiks:</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="start_time" name="start_time" class="flatpickr-input flatpickr-time" required
                        readonly="readonly">
                    <input type="hidden" name="start_time">
                </div>
            </div>
            <div class="date-time-group">
                <div>
                    <label for="end_date">Beigu datums:</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="end_date" name="end_date" class="flatpickr-input flatpickr-date" required
                        readonly="readonly">
                    <input type="hidden" name="end_date">
                </div>
                <div>
                    <label for="end_time">Beigu laiks:</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="end_time" name="end_time" class="flatpickr-input flatpickr-time" required
                        readonly="readonly">
                    <input type="hidden" name="end_time">
                </div>
            </div>

            <button type="submit">Pareģot</button>
        </form>

    </div>
    <!-- Include flatpickr JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".flatpickr-date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function (selectedDates, dateStr, instance) {
                // Update the hidden input value when the date changes
                instance.input.nextElementSibling.value = dateStr;
            },
        });

        flatpickr(".flatpickr-time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            onChange: function (selectedDates, dateStr, instance) {
                // Update the hidden input value when the date changes
                instance.input.nextElementSibling.value = dateStr;
            },
        });

    </script>
</body>

</html>
