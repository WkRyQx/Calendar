let draggedEvent = null;

function dragstartHandler(e) {
    draggedEvent = e.target;

    // eredeti állapot mentése csak egyszer
    if (!draggedEvent.dataset.originalSaved) {
        const lines = draggedEvent.innerHTML.split('<br>');
        const timeText = lines[1];
        const [startTime, endTime] = timeText.split(' - ');

        draggedEvent.dataset.originalTop = draggedEvent.style.top;
        draggedEvent.dataset.originalHeight = draggedEvent.style.height;
        draggedEvent.dataset.originalStart = startTime.trim();
        draggedEvent.dataset.originalEnd = endTime.trim();
        draggedEvent.dataset.originalHTML = draggedEvent.innerHTML;
        draggedEvent.dataset.originalSaved = "true";
    }

    e.dataTransfer.effectAllowed = "move";
}

function dropHandler(e) {
    e.preventDefault();
    if (!draggedEvent) return;

    const grid = document.querySelector('.calendar-grid');
    const rect = grid.getBoundingClientRect();
    const y = e.clientY - rect.top + grid.scrollTop;

    const height = draggedEvent.offsetHeight;
    draggedEvent.style.top = y + "px";

    const startMinutes = Math.round(y);
    const endMinutes = startMinutes + height;

    const newStart = formatTime(startMinutes);
    const newEnd = formatTime(endMinutes);

    // UI frissítés
    const lines = draggedEvent.innerHTML.split('<br>');
    draggedEvent.innerHTML =
        lines[0] + "<br>" +
        newStart + " - " + newEnd +
        `<button class="undo-btn">Vissza</button>`;

    // ====== DB UPDATE (POST, JSON NÉLKÜL) ======
    const id = draggedEvent.dataset.eventId;
    const date = draggedEvent.dataset.date;

    const params = new URLSearchParams();
    params.append("id", id);
    params.append("start", newStart);
    params.append("end", newEnd);
    params.append("date", date);

    fetch("update_event.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
    });
}

function dragoverHandler(e) {
    e.preventDefault();
}

// idő formázás
function formatTime(min) {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return h.toString().padStart(2, '0') + ":" + m.toString().padStart(2, '0');
}

// ====== UNDO (MINDEN ESEMÉNY SAJÁT MAGÁT KEZELI) ======
document.addEventListener("click", function(e) {

    if (e.target.classList.contains("undo-btn")) {

        const eventDiv = e.target.closest(".event");

        const originalStart = eventDiv.dataset.originalStart;
        const originalEnd = eventDiv.dataset.originalEnd;
        const id = eventDiv.dataset.eventId;
        const date = eventDiv.dataset.date;

        // UI vissza
        eventDiv.style.top = eventDiv.dataset.originalTop;
        eventDiv.style.height = eventDiv.dataset.originalHeight;
        eventDiv.innerHTML = eventDiv.dataset.originalHTML;

        // ===== DB VISSZA =====
        const params = new URLSearchParams();
        params.append("id", id);
        params.append("start", originalStart);
        params.append("end", originalEnd);
        params.append("date", date);

        fetch("update_event.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: params.toString()
        });
    }
});