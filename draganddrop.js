let draggedEvent = null;

function dragstartHandler(e) {
    draggedEvent = e.target;
}

function dragoverHandler(e) {
    e.preventDefault();
}

function dropHandler(e) {
    e.preventDefault();

    if (!draggedEvent) return;

    const id = draggedEvent.dataset.eventId;
    const date = draggedEvent.dataset.date;

    console.log("ID:", id, "DATE:", date);

    const newStart = "10:00";
    const newEnd = "11:00";

    const params = new URLSearchParams();
    params.append("id", id);
    params.append("start", newStart);
    params.append("end", newEnd);
    params.append("date", date);

    fetch("update_event.php", {
        method: "POST",
        body: params
    })
        .then(r => r.text())
        .then(d => console.log("SERVER:", d))
        .catch(err => console.error(err));
}