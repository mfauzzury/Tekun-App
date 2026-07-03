/**
 * Client-side export utilities (CSV, Excel, PDF) - no server required
 */

export function exportToCSV(data: Record<string, unknown>[], filename: string) {
  if (data.length === 0) return;
  const headers = Object.keys(data[0]);
  const csv = [
    headers.join(","),
    ...data.map((row) => headers.map((h) => `"${String(row[h] ?? "").replace(/"/g, '""')}"`).join(",")),
  ].join("\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" });
  downloadBlob(blob, `${filename}.csv`);
}

export function exportToExcel(data: Record<string, unknown>[], filename: string) {
  if (data.length === 0) return;
  const headers = Object.keys(data[0]);
  const html = `
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>${filename}</title></head>
<body>
<table border="1">
<thead><tr>${headers.map((h) => `<th>${h}</th>`).join("")}</tr></thead>
<tbody>
${data.map((row) => `<tr>${headers.map((h) => `<td>${String(row[h] ?? "")}</td>`).join("")}</tr>`).join("")}
</tbody>
</table>
</body>
</html>`;
  const blob = new Blob([html], { type: "application/vnd.ms-excel" });
  downloadBlob(blob, `${filename}.xls`);
}

export function exportToPDF(elementId: string, filename: string) {
  const el = document.getElementById(elementId);
  if (!el) return;
  const printWindow = window.open("", "_blank");
  if (!printWindow) {
    window.print();
    return;
  }
  printWindow.document.write(`
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>${filename}</title>
<style>
body{font-family:system-ui,sans-serif;padding:20px;font-size:12px}
table{border-collapse:collapse;width:100%;margin-top:16px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#f5f5f5}
</style>
</head>
<body>
${el.innerHTML}
</body>
</html>`);
  printWindow.document.close();
  printWindow.onload = () => {
    printWindow.print();
    printWindow.close();
  };
}

function downloadBlob(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
