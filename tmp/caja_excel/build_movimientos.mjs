import fs from "node:fs/promises";
import { Workbook, SpreadsheetFile } from "@oai/artifact-tool";

const RATE = 36.45;
const outputDir = "../../outputs/01a01d63-1c3f-7a83-bb6f-b407c4771734";
const outputPath = `${outputDir}/Movimientos_Northlink_Agosto_2026.xlsx`;

const txs = [];
let id = 1;
function add(date, account, currency, description, debit = 0, credit = 0, category = "Pendiente de clasificar", treatment = "Pendiente", responsible = "", period = "Agosto 2026", project = "", status = "Pendiente") {
  txs.push({ id: id++, date: new Date(`${date}T12:00:00`), account, currency, description, debit, credit, category, treatment, responsible, period, project, status });
}

// Cuenta USD - agosto 2026 (orden cronologico).
add("2026-08-03", "USD 34232261", "USD", "Transferencia entre cuentas", 0, 50, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-03", "USD 34232261", "USD", "Estacion de Servicio Esso Esteli", 9.05, 0, "Combustible", "Gasto empresarial", "", "Agosto 2026", "");
add("2026-08-03", "USD 34232261", "USD", "Retiro ATM Matagalpa 3", 27.43, 0, "Combustible entregado", "Anticipo por liquidar", "Jairo", "Agosto 2026", "");
add("2026-08-03", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-03", "USD 34232261", "USD", "Claro Mi Claro Express", 3.29, 0, "Telecomunicaciones", "Pendiente", "Harrinton");
add("2026-08-03", "USD 34232261", "USD", "Claro Mi Claro Express", 3.29, 0, "Telecomunicaciones", "Pendiente", "Harrinton");
add("2026-08-03", "USD 34232261", "USD", "Jamylas Caffe La Dalia", 5.34, 0, "Viaticos", "Gasto empresarial", "Equipo Northlink", "Agosto 2026", "Viaje laboral");
add("2026-08-03", "USD 34232261", "USD", "Transferencia entre cuentas", 0, 20, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-03", "USD 34232261", "USD", "Envio a Bany", 16.43, 0, "Viaticos - hotel", "Gasto empresarial", "Bany", "Agosto 2026", "Viaje Waslala");
add("2026-08-04", "USD 34232261", "USD", "Envio a mi cuenta dolares", 0, 134.48, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-05", "USD 34232261", "USD", "Envio a Blanca", 27.43, 0, "Viaticos - alimentacion", "Gasto empresarial", "Blanca", "Agosto 2026", "Viaje Waslala");
add("2026-08-07", "USD 34232261", "USD", "Cursor AI", 20.00, 0, "Software y suscripciones", "Pendiente", "Northlink");
add("2026-08-07", "USD 34232261", "USD", "Claro Mi Claro Express", 3.29, 0, "Telecomunicaciones", "Pendiente", "Harrinton");
add("2026-08-07", "USD 34232261", "USD", "Facebook", 5.16, 0, "Publicidad", "Pendiente", "Northlink");
add("2026-08-07", "USD 34232261", "USD", "Puma La Dalia", 9.05, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-07", "USD 34232261", "USD", "Jamylas Caffe La Dalia", 3.70, 0, "Viaticos", "Gasto empresarial", "Equipo Northlink", "Agosto 2026", "Viaje laboral");
add("2026-08-07", "USD 34232261", "USD", "Jamylas Caffe La Dalia", 2.19, 0, "Viaticos", "Gasto empresarial", "Equipo Northlink", "Agosto 2026", "Viaje laboral");
add("2026-08-07", "USD 34232261", "USD", "Envio a Jairo", 40.00, 0, "Nomina", "Gasto empresarial", "Axel", "Agosto 2026", "Incluido en pago de Axel");
add("2026-08-07", "USD 34232261", "USD", "PedidosYa Little Caesar", 13.14, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-08", "USD 34232261", "USD", "PedidosYa propina", 0.79, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-08", "USD 34232261", "USD", "Retiro ATM Unopetrol Esteli", 13.71, 0, "Retiro sin justificar", "Pendiente", "");
add("2026-08-08", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-09", "USD 34232261", "USD", "Facebook", 5.24, 0, "Publicidad", "Pendiente", "Northlink");
add("2026-08-09", "USD 34232261", "USD", "Reversion Facebook", 0, 5.16, "Reversion/reembolso", "Reduce gasto", "Facebook", "Agosto 2026", "", "Revisado");
add("2026-08-11", "USD 34232261", "USD", "Pago salario Axell + 20 USD Onell", 0, 130.46, "Entrada no comercial", "Pendiente", "Axel / Harrinton");
add("2026-08-11", "USD 34232261", "USD", "Retiro ATM Bancentro Esteli", 123.45, 0, "Caja chica", "Fondo por liquidar", "Caja chica");
add("2026-08-11", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-12", "USD 34232261", "USD", "Envio a mi cuenta dolares", 0, 2.69, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-12", "USD 34232261", "USD", "Cloudflare", 10.46, 0, "Software y suscripciones", "Pendiente", "Northlink");
add("2026-08-13", "USD 34232261", "USD", "Resto de efectivo en la cuenta", 0, 200.00, "Fondos de periodo anterior", "No es ingreso de agosto", "Aaron", "Julio 2026", "Ferreteria Ruiz", "Revisado");
add("2026-08-13", "USD 34232261", "USD", "Envio a Ronald", 45.00, 0, "Viaticos - alimentacion", "Gasto empresarial", "Ronald", "Agosto 2026", "Viaje Waslala");
add("2026-08-13", "USD 34232261", "USD", "Anticipo Sistema Contable", 0, 3540.00, "Ingreso cliente", "Ingreso empresarial", "Don Roger", "Agosto 2026", "Lacteos Quebar", "Revisado");
add("2026-08-13", "USD 34232261", "USD", "Envio a Jairo", 25.00, 0, "Combustible", "Gasto empresarial", "Jairo");
add("2026-08-14", "USD 34232261", "USD", "Mi Claro Express", 3.01, 0, "Telecomunicaciones", "Pendiente", "Harrinton");
add("2026-08-14", "USD 34232261", "USD", "Guayacan Coffee", 38.93, 0, "Viaticos", "Gasto empresarial", "Equipo Northlink", "Agosto 2026", "Viaje laboral");
add("2026-08-14", "USD 34232261", "USD", "Transferencia entre cuentas", 0, 300.00, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-15", "USD 34232261", "USD", "Retiro ATM El Rosario", 384.08, 0, "Retiro para nomina", "Fondo por liquidar", "Nomina");
add("2026-08-15", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-15", "USD 34232261", "USD", "Transferencia a cuenta NIO - nomina Harrinton", 148.83, 0, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-15", "USD 34232261", "USD", "Transferencia a cuenta NIO - nomina Harrinton", 148.83, 0, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-15", "USD 34232261", "USD", "Pago de nomina 15 de agosto", 148.15, 0, "Nomina", "Gasto empresarial", "Aaron");
add("2026-08-16", "USD 34232261", "USD", "Mr Vinks", 18.65, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-16", "USD 34232261", "USD", "Mr Vinks", 5.48, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-17", "USD 34232261", "USD", "Envio a Brayan", 40.00, 0, "Equipos de proyecto", "Gasto empresarial", "Brayan", "Agosto 2026", "Don Ramiro Ramirez");
add("2026-08-17", "USD 34232261", "USD", "Envio a Jairo", 70.00, 0, "Combustible y viaticos", "Gasto empresarial", "Jairo", "Agosto 2026", "US$50 combustible + US$20 viatico");
add("2026-08-17", "USD 34232261", "USD", "FS137 Esteli", 36.36, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-17", "USD 34232261", "USD", "Miscelanea Buen Precio", 3.70, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-17", "USD 34232261", "USD", "Intereses", 0, 0.18, "Intereses bancarios", "Ingreso financiero", "Banco", "Agosto 2026", "", "Revisado");
add("2026-08-17", "USD 34232261", "USD", "Retencion de intereses", 0.02, 0, "Comisiones e impuestos bancarios", "Gasto empresarial", "Banco");
add("2026-08-18", "USD 34232261", "USD", "Envio a Jairo", 760.00, 0, "Anticipo mantenimiento vehiculo", "Fondo por liquidar", "Jairo", "Agosto 2026", "Camioneta Northlink");
add("2026-08-18", "USD 34232261", "USD", "Jul 2026", 8.43, 0, "Servicio sin identificar", "Pendiente", "Harrinton", "Julio 2026");
add("2026-08-18", "USD 34232261", "USD", "Envio a Yamileth", 120.00, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-18", "USD 34232261", "USD", "Envio a Jose", 35.00, 0, "Viaticos", "Gasto empresarial", "Jose", "Agosto 2026", "Instalacion de camaras");
add("2026-08-18", "USD 34232261", "USD", "Deposito en efectivo", 0, 640.00, "Ingreso cliente", "Ingreso empresarial", "Don Norvin", "Agosto 2026", "Camaras de seguridad", "Revisado");
add("2026-08-18", "USD 34232261", "USD", "Deposito en efectivo", 0, 1230.00, "Ingreso cliente", "Ingreso empresarial", "Don Norvin", "Agosto 2026", "Camaras de seguridad", "Revisado");
add("2026-08-18", "USD 34232261", "USD", "Deposito en efectivo", 0, 280.00, "Ingreso cliente", "Ingreso empresarial", "Don Norvin", "Agosto 2026", "Camaras de seguridad", "Revisado");
add("2026-08-19", "USD 34232261", "USD", "HBO Max", 5.02, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-19", "USD 34232261", "USD", "Apple.com/bill", 1.39, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-19", "USD 34232261", "USD", "Envio a Ariadna", 1545.77, 0, "Equipos de proyecto", "Gasto empresarial", "Ariadna", "Agosto 2026", "Don Norvin - camaras");
add("2026-08-19", "USD 34232261", "USD", "Buffett Esteli", 18.93, 0, "Consumo personal recuperable", "Recuperable/personal", "Greyvin / Axel / Harrinton", "Agosto 2026", "C$220 Greyvin, C$220 Axel, resto Harrinton");
add("2026-08-19", "USD 34232261", "USD", "Envio a F", 193.00, 0, "Equipos de proyecto", "Gasto empresarial", "F", "Agosto 2026", "Disco duro - Don Norvin");
add("2026-08-19", "USD 34232261", "USD", "Envio a Carlos", 20.30, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-20", "USD 34232261", "USD", "Compra de recarga Tigo", 14.27, 0, "Telecomunicaciones", "Pendiente", "Harrinton");
add("2026-08-20", "USD 34232261", "USD", "Reversion HBO Max", 0, 5.02, "Reversion/reembolso", "Reduce gasto", "HBO Max", "Agosto 2026", "", "Revisado");
add("2026-08-20", "USD 34232261", "USD", "HBO Max", 5.10, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-20", "USD 34232261", "USD", "Retiro ATM Sucursal Matagalpa", 548.69, 0, "Prestamo a empleado", "Cuenta por cobrar", "Axel", "Agosto 2026", "Reparacion camioneta personal");
add("2026-08-20", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-20", "USD 34232261", "USD", "Retiro ATM Sucursal Matagalpa", 54.86, 0, "Caja chica", "Fondo por liquidar", "Caja chica");
add("2026-08-20", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-20", "USD 34232261", "USD", "ECSA UNO Estrella del Norte", 80.32, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-20", "USD 34232261", "USD", "ECSA UNO Estrella del Norte", 5.62, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-20", "USD 34232261", "USD", "Carls Junior Tramontana", 29.41, 0, "Mixto: viatico y recuperable", "Mixto", "Harrinton / Jairo", "Agosto 2026", "C$600 recuperable; resto viatico");
add("2026-08-21", "USD 34232261", "USD", "Deposito/pago de renta de modulo", 500.00, 0, "Renta de modulo", "Pendiente", "Northlink");
add("2026-08-22", "USD 34232261", "USD", "Super Express El Calvario", 9.01, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-22", "USD 34232261", "USD", "Bar Sopas El Carao", 37.31, 0, "Consumo personal recuperable", "Recuperable/personal", "Axel / Jairo / Greyvin / Harrinton");
add("2026-08-22", "USD 34232261", "USD", "Super Express Los Coquitos", 5.33, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-23", "USD 34232261", "USD", "Don Vigoron Esteli", 8.23, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-23", "USD 34232261", "USD", "ECSA UNO Esteli Norte", 8.23, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-23", "USD 34232261", "USD", "Retiro ATM El Rosario", 54.86, 0, "Caja chica", "Fondo por liquidar", "Caja chica");
add("2026-08-23", "USD 34232261", "USD", "Comision retiro ATM", 0.03, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-23", "USD 34232261", "USD", "Teriyaki Esteli", 13.44, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-24", "USD 34232261", "USD", "Reversion Spotify", 0, 2.00, "Reversion/reembolso", "Reduce gasto", "Spotify", "Agosto 2026", "", "Revisado");
add("2026-08-24", "USD 34232261", "USD", "Spotify", 2.00, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-24", "USD 34232261", "USD", "Miscelanea Buen Precio", 12.75, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-24", "USD 34232261", "USD", "Compra de paquete Tigo", 6.04, 0, "Telecomunicaciones", "Pendiente", "Harrinton");

// Cuenta NIO - agosto 2026.
add("2026-08-04", "NIO 39081312", "NIO", "Sistema Negocio", 0, 16425.00, "Ingreso cliente", "Ingreso empresarial", "Rosidani", "Agosto 2026", "Vulcanizadora Rios", "Revisado");
add("2026-08-04", "NIO 39081312", "NIO", "Google One", 10.78, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-04", "NIO 39081312", "NIO", "Envio a Yadira", 710.00, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-04", "NIO 39081312", "NIO", "Envio a mi cuenta dolares", 5000.00, 0, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-04", "NIO 39081312", "NIO", "Envio a Greyvis", 6000.00, 0, "Nomina atrasada", "Gasto periodo anterior", "Greyvis", "Julio 2026");
add("2026-08-04", "NIO 39081312", "NIO", "UNO Waslala", 260.00, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-04", "NIO 39081312", "NIO", "Retiro ATM Bancentro Waslala", 2000.00, 0, "Viaticos en efectivo", "Fondo por liquidar", "Equipo Northlink", "Agosto 2026", "Viaje Waslala");
add("2026-08-04", "NIO 39081312", "NIO", "Comision retiro ATM", 1.10, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-05", "NIO 39081312", "NIO", "Envio a Erick", 200.00, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-05", "NIO 39081312", "NIO", "Retiro ATM Bancentro Waslala", 2000.00, 0, "Retiro sin justificar", "Pendiente", "");
add("2026-08-05", "NIO 39081312", "NIO", "Comision retiro ATM", 1.10, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-05", "NIO 39081312", "NIO", "Enviado por Michael", 0, 999.00, "Entrada personal", "No es ingreso empresarial", "Michael");
add("2026-08-07", "NIO 39081312", "NIO", "Restaurante Tip Top Matagalpa", 600.00, 0, "Viaticos", "Gasto empresarial", "Equipo Northlink", "Agosto 2026", "Viaje Waslala");
add("2026-08-08", "NIO 39081312", "NIO", "Retiro ATM Unopetrol Esteli", 500.00, 0, "Retiro sin justificar", "Pendiente", "");
add("2026-08-08", "NIO 39081312", "NIO", "Comision retiro ATM", 1.10, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-12", "NIO 39081312", "NIO", "Envio a mi cuenta dolares", 100.00, 0, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-15", "NIO 39081312", "NIO", "Retiro ATM El Rosario", 3500.00, 0, "Retiro para nomina", "Fondo por liquidar", "Nomina");
add("2026-08-15", "NIO 39081312", "NIO", "Comision retiro ATM", 1.10, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-15", "NIO 39081312", "NIO", "Pago planilla agosto primera quincena", 4659.39, 0, "Nomina", "Gasto empresarial", "Jairo");
add("2026-08-15", "NIO 39081312", "NIO", "Envio a Jairo", 1000.00, 0, "Pago de deuda personal", "Recuperable/personal", "Harrinton");
add("2026-08-15", "NIO 39081312", "NIO", "The Coffee Hub", 355.00, 0, "Consumo personal", "Recuperable/personal", "Harrinton");
add("2026-08-15", "NIO 39081312", "NIO", "Spotify", 241.29, 0, "Software y suscripciones", "Pendiente", "Harrinton");
add("2026-08-15", "NIO 39081312", "NIO", "Transferencia desde cuenta USD", 0, 5425.00, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-15", "NIO 39081312", "NIO", "Transferencia desde cuenta USD", 0, 5425.00, "Transferencia interna", "No es ingreso ni gasto", "Harrinton", "Agosto 2026", "", "Revisado");
add("2026-08-16", "NIO 39081312", "NIO", "Puma Santa Lucia", 200.00, 0, "Combustible", "Gasto empresarial", "");
add("2026-08-17", "NIO 39081312", "NIO", "Cargo por saldo menor al limite", 150.00, 0, "Comisiones bancarias", "Gasto empresarial", "Banco");
add("2026-08-17", "NIO 39081312", "NIO", "Intereses", 0, 0.05, "Intereses bancarios", "Ingreso financiero", "Banco", "Agosto 2026", "", "Revisado");
add("2026-08-19", "NIO 39081312", "NIO", "Ago 2026", 180.77, 0, "Servicio sin identificar", "Pendiente", "Harrinton");

txs.sort((a, b) => a.date - b.date || a.account.localeCompare(b.account) || a.id - b.id);

const wb = Workbook.create();
const summary = wb.worksheets.add("Resumen");
const movements = wb.worksheets.add("Movimientos");
const declared = wb.worksheets.add("Declarados fuera banco");
const catalog = wb.worksheets.add("Catalogos");
const checks = wb.worksheets.add("Controles");
const sources = wb.worksheets.add("Fuentes");

const navy = "#123047";
const teal = "#0F766E";
const lightBlue = "#E8F1F5";
const inputYellow = "#FFF3BF";
const green = "#DCFCE7";
const red = "#FEE2E2";
const gray = "#F3F4F6";

// Catalogos y validaciones.
const categories = [...new Set(txs.map(t => t.category).concat(["Ingreso cliente", "Gasto empresarial", "Nomina corriente", "Nomina atrasada", "Prestamo", "Anticipo por liquidar", "Caja chica", "Consumo personal", "Transferencia interna", "Pendiente de clasificar"]))].sort();
const treatments = ["Gasto empresarial", "Ingreso empresarial", "Cuenta por cobrar", "Fondo por liquidar", "Recuperable/personal", "No es ingreso ni gasto", "No es ingreso empresarial", "No es ingreso de agosto", "Gasto periodo anterior", "Ingreso financiero", "Reduce gasto", "Mixto", "Pendiente"];
const reviewStatuses = ["Pendiente", "Justificado", "Revisado", "Requiere comprobante", "Descartado"];
catalog.getRange("A1:C1").values = [["Categorias", "Tratamientos", "Estados de revision"]];
const catRows = Math.max(categories.length, treatments.length, reviewStatuses.length);
const catMatrix = Array.from({length: catRows}, (_, i) => [categories[i] ?? null, treatments[i] ?? null, reviewStatuses[i] ?? null]);
catalog.getRange(`A2:C${catRows + 1}`).values = catMatrix;
catalog.getRange("A1:C1").format = { fill: navy, font: { bold: true, color: "#FFFFFF" } };
catalog.getRange(`A1:C${catRows + 1}`).format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
catalog.getRange("A:C").format.columnWidth = 26;
catalog.freezePanes.freezeRows(1);

// Movimientos.
movements.showGridLines = false;
movements.mergeCells("A1:X1");
movements.getRange("A1").values = [["NORTHLINK MICROSYSTEM - CONCILIACION DE MOVIMIENTOS AGOSTO 2026"]];
movements.getRange("A1:X1").format = { fill: navy, font: { bold: true, color: "#FFFFFF", size: 16 }, horizontalAlignment: "center", rowHeight: 30 };
movements.mergeCells("A2:X2");
movements.getRange("A2").values = [["Complete las columnas amarillas. Los importes en USD se calculan con la tasa indicada en cada fila."]];
movements.getRange("A2:X2").format = { fill: lightBlue, font: { color: navy, italic: true }, horizontalAlignment: "left", rowHeight: 24 };

const headers = ["ID", "Fecha", "Cuenta", "Moneda", "Descripcion bancaria", "Debito original", "Credito original", "TC C$/US$", "Debito USD", "Credito USD", "Flujo neto USD", "Naturaleza", "Categoria sugerida", "Tratamiento sugerido", "Responsable sugerido", "Periodo sugerido", "Justificacion del usuario", "Categoria final", "Tratamiento final", "Responsable final", "Proyecto/cliente final", "Comprobante o referencia", "Estado revision", "Observaciones"];
movements.getRange("A5:X5").values = [headers];
movements.getRange("A5:X5").format = { fill: teal, font: { bold: true, color: "#FFFFFF" }, wrapText: true, horizontalAlignment: "center", verticalAlignment: "center", rowHeight: 42 };

const rows = txs.map(t => [t.id, t.date, t.account, t.currency, t.description, t.debit || 0, t.credit || 0, t.currency === "USD" ? 1 : RATE, null, null, null, t.debit > 0 ? "Salida" : "Entrada", t.category, t.treatment, t.responsible, t.period, null, t.category, t.treatment, t.responsible, t.project, null, t.status, null]);
const firstRow = 6;
const lastRow = firstRow + rows.length - 1;
movements.getRange(`A${firstRow}:X${lastRow}`).values = rows;
movements.getRange(`I${firstRow}`).formulas = [[`=IF(D${firstRow}="USD",F${firstRow},F${firstRow}/H${firstRow})`]];
movements.getRange(`I${firstRow}:I${lastRow}`).fillDown();
movements.getRange(`J${firstRow}`).formulas = [[`=IF(D${firstRow}="USD",G${firstRow},G${firstRow}/H${firstRow})`]];
movements.getRange(`J${firstRow}:J${lastRow}`).fillDown();
movements.getRange(`K${firstRow}`).formulas = [[`=J${firstRow}-I${firstRow}`]];
movements.getRange(`K${firstRow}:K${lastRow}`).fillDown();

movements.getRange(`B${firstRow}:B${lastRow}`).format.numberFormat = "yyyy-mm-dd";
movements.getRange(`F${firstRow}:G${lastRow}`).format.numberFormat = "#,##0.00;[Red](#,##0.00);-";
movements.getRange(`H${firstRow}:H${lastRow}`).format.numberFormat = "0.00";
movements.getRange(`I${firstRow}:K${lastRow}`).format.numberFormat = '"US$"#,##0.00;[Red]("US$"#,##0.00);-';
movements.getRange(`A${firstRow}:X${lastRow}`).format = { verticalAlignment: "top", rowHeight: 30 };
movements.getRange(`E${firstRow}:X${lastRow}`).format.wrapText = true;
movements.getRange(`Q${firstRow}:X${lastRow}`).format.fill = inputYellow;
movements.getRange(`A${firstRow}:X${lastRow}`).format.borders = { preset: "inside", style: "thin", color: "#E5E7EB" };

movements.getRange(`R${firstRow}:R${lastRow}`).dataValidation = { rule: { type: "list", formula1: `'Catalogos'!$A$2:$A$${categories.length + 1}` } };
movements.getRange(`S${firstRow}:S${lastRow}`).dataValidation = { rule: { type: "list", formula1: `'Catalogos'!$B$2:$B$${treatments.length + 1}` } };
movements.getRange(`W${firstRow}:W${lastRow}`).dataValidation = { rule: { type: "list", formula1: `'Catalogos'!$C$2:$C$${reviewStatuses.length + 1}` } };
movements.getRange(`W${firstRow}:W${lastRow}`).conditionalFormats.add("containsText", { text: "Pendiente", format: { fill: red, font: { color: "#991B1B", bold: true } } });
movements.getRange(`W${firstRow}:W${lastRow}`).conditionalFormats.add("containsText", { text: "Justificado", format: { fill: green, font: { color: "#166534", bold: true } } });

const widths = [7, 12, 16, 9, 34, 15, 15, 12, 14, 14, 15, 11, 25, 24, 22, 14, 36, 25, 24, 22, 24, 25, 18, 32];
widths.forEach((w, i) => movements.getRangeByIndexes(0, i, lastRow, 1).format.columnWidth = w);
movements.freezePanes.freezeRows(5);
movements.freezePanes.freezeColumns(5);
const table = movements.tables.add(`A5:X${lastRow}`, true, "MovimientosAgosto");
table.style = "TableStyleMedium2";

// Resumen formula-driven.
summary.showGridLines = false;
summary.mergeCells("A1:F1");
summary.getRange("A1").values = [["RESUMEN DE CONCILIACION - AGOSTO 2026"]];
summary.getRange("A1:F1").format = { fill: navy, font: { bold: true, color: "#FFFFFF", size: 16 }, horizontalAlignment: "center", rowHeight: 30 };
summary.getRange("A3:B3").values = [["Indicador", "Monto USD"]];
summary.getRange("A3:B3").format = { fill: teal, font: { bold: true, color: "#FFFFFF" } };
summary.getRange("A4:A10").values = [["Debitos brutos"], ["Creditos brutos"], ["Salidas por transferencias internas"], ["Salidas externas brutas"], ["Reversiones/reembolsos"], ["Salidas externas netas"], ["Movimientos pendientes de justificar"]];
summary.getRange("B4").formulas = [[`=SUM('Movimientos'!I${firstRow}:I${lastRow})`]];
summary.getRange("B5").formulas = [[`=SUM('Movimientos'!J${firstRow}:J${lastRow})`]];
summary.getRange("B6").formulas = [[`=SUMIF('Movimientos'!R${firstRow}:R${lastRow},"Transferencia interna",'Movimientos'!I${firstRow}:I${lastRow})`]];
summary.getRange("B7").formulas = [["=B4-B6"]];
summary.getRange("B8").formulas = [[`=SUMIF('Movimientos'!R${firstRow}:R${lastRow},"Reversion/reembolso",'Movimientos'!J${firstRow}:J${lastRow})`]];
summary.getRange("B9").formulas = [["=B7-B8"]];
summary.getRange("B10").formulas = [[`=COUNTIF('Movimientos'!W${firstRow}:W${lastRow},"Pendiente")`]];
summary.getRange("B4:B9").format.numberFormat = '"US$"#,##0.00;[Red]("US$"#,##0.00);-';
summary.getRange("B10").format.numberFormat = "#,##0";
summary.getRange("A4:B10").format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
summary.getRange("A12:B12").values = [["Categoria final", "Debitos USD"]];
summary.getRange("A12:B12").format = { fill: teal, font: { bold: true, color: "#FFFFFF" } };
const summaryCats = categories.filter(c => !["Ingreso cliente", "Intereses bancarios", "Reversion/reembolso"].includes(c));
summary.getRange(`A13:A${12 + summaryCats.length}`).values = summaryCats.map(c => [c]);
summary.getRange(`B13:B${12 + summaryCats.length}`).formulas = summaryCats.map((_, idx) => [`=SUMIF('Movimientos'!$R$${firstRow}:$R$${lastRow},A${13 + idx},'Movimientos'!$I$${firstRow}:$I$${lastRow})`]);
summary.getRange(`B13:B${12 + summaryCats.length}`).format.numberFormat = '"US$"#,##0.00;[Red]("US$"#,##0.00);-';
summary.getRange(`A12:B${12 + summaryCats.length}`).format.borders = { preset: "inside", style: "thin", color: "#E5E7EB" };
summary.getRange("D3:E3").values = [["Dato de control", "Valor"]];
summary.getRange("D3:E3").format = { fill: teal, font: { bold: true, color: "#FFFFFF" } };
summary.getRange("D4:D8").values = [["Tasa usada C$/US$"], ["Cuenta USD - debitos"], ["Cuenta USD - creditos"], ["Cuenta NIO - debitos"], ["Cuenta NIO - creditos"]];
summary.getRange("E4").values = [[RATE]];
summary.getRange("E5").formulas = [[`=SUMIF('Movimientos'!C${firstRow}:C${lastRow},"USD 34232261",'Movimientos'!F${firstRow}:F${lastRow})`]];
summary.getRange("E6").formulas = [[`=SUMIF('Movimientos'!C${firstRow}:C${lastRow},"USD 34232261",'Movimientos'!G${firstRow}:G${lastRow})`]];
summary.getRange("E7").formulas = [[`=SUMIF('Movimientos'!C${firstRow}:C${lastRow},"NIO 39081312",'Movimientos'!F${firstRow}:F${lastRow})`]];
summary.getRange("E8").formulas = [[`=SUMIF('Movimientos'!C${firstRow}:C${lastRow},"NIO 39081312",'Movimientos'!G${firstRow}:G${lastRow})`]];
summary.getRange("E4:E8").format.numberFormat = "#,##0.00";
summary.getRange("D4:E8").format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
summary.getRange("A:B").format.columnWidth = 34;
summary.getRange("D:D").format.columnWidth = 28;
summary.getRange("E:E").format.columnWidth = 18;
summary.freezePanes.freezeRows(3);
summary.unmergeCells("A1:F1");
summary.getRange("A1").values = [["RESUMEN DE CONCILIACION - AGOSTO 2026"]];
summary.getRange("A1:F1").format = { fill: navy, font: { bold: true, color: "#FFFFFF", size: 16 }, horizontalAlignment: "left", rowHeight: 30 };

// Movimientos informados verbalmente que no deben sumarse hasta vincularlos con un retiro o deposito bancario.
declared.showGridLines = false;
declared.mergeCells("A1:K1");
declared.getRange("A1").values = [["MOVIMIENTOS DECLARADOS FUERA DEL BANCO O PENDIENTES DE VINCULAR"]];
declared.getRange("A1:K1").format = { fill: navy, font: { bold: true, color: "#FFFFFF", size: 15 }, horizontalAlignment: "center", rowHeight: 30 };
declared.mergeCells("A2:K2");
declared.getRange("A2").values = [["No sumar automáticamente con los movimientos bancarios: primero indique en qué retiro, depósito o efectivo se encuentra cada operación."]];
declared.getRange("A2:K2").format = { fill: lightBlue, font: { italic: true, color: navy }, wrapText: true, rowHeight: 28 };
declared.getRange("A4:K4").values = [["Fecha aprox.", "Tipo", "Persona/cliente", "Moneda", "Monto original", "Monto USD", "Relacion bancaria", "Movimiento bancario relacionado", "Justificacion", "Estado", "Notas"]];
declared.getRange("A4:K4").format = { fill: teal, font: { bold: true, color: "#FFFFFF" }, wrapText: true, rowHeight: 38 };
const declaredRows = [
  [new Date("2026-08-04T12:00:00"), "Entrada cliente", "Kevin - R & B Celulares", "USD", 420, 420, "No localizado", "", "", "Pendiente", "Cobro confirmado; falta indicar dónde quedó o en qué se utilizó."],
  [new Date("2026-08-06T12:00:00"), "Entrada cliente", "Don Hebbert", "USD", 300, 300, "No localizado", "", "", "Pendiente", "Anticipo confirmado."],
  [new Date("2026-08-18T12:00:00"), "Entrada cliente", "Don Norvin", "USD", 10, 10, "No localizado", "", "", "Pendiente", "Diferencia entre US$2,160 recibidos y US$2,150 depositados."],
  [new Date("2026-08-19T12:00:00"), "Entrada cliente", "Yeris Altamirano", "USD", 590, 590, "Efectivo en custodia", "", "", "Pendiente", "Jairo conserva el efectivo y debe entregarlo."],
  [new Date("2026-08-15T12:00:00"), "Salida nomina", "Greyvin", "NIO", 4918, 4918 / RATE, "No localizado", "", "", "Pendiente", "Pago corriente reportado; identificar transferencia o retiro."],
  [new Date("2026-08-15T12:00:00"), "Salida nomina", "Axel", "NIO", 5254, 5254 / RATE, "Parcial", "Envio a Jairo por US$40 + efectivo", "", "Pendiente", "El total informado incluye los US$40 enviados mediante Jairo."],
  [new Date("2026-08-15T12:00:00"), "Salida nomina", "Harrinton", "NIO", 5500, 5500 / RATE, "No localizado", "", "", "Pendiente", "Identificar si salió del retiro de C$17,500."],
  [new Date("2026-08-15T12:00:00"), "Adelanto nomina", "Greyvin", "NIO", 2500, 2500 / RATE, "No localizado", "", "", "Pendiente", "Adelanto recuperable/descontable."],
  [new Date("2026-08-15T12:00:00"), "Nomina atrasada", "Axel", "USD", 125, 125, "No localizado", "", "", "Pendiente", "Quincena de periodo anterior."],
  [new Date("2026-08-15T12:00:00"), "Nomina atrasada", "Jairo / Harrinton / Aaron", "USD", 150, 150, "No localizado", "", "", "Pendiente", "US$150 totales aproximados; confirmar distribución exacta."],
];
declared.getRange(`A5:K${4 + declaredRows.length}`).values = declaredRows;
declared.getRange(`A5:A${4 + declaredRows.length}`).format.numberFormat = "yyyy-mm-dd";
declared.getRange(`E5:E${4 + declaredRows.length}`).format.numberFormat = "#,##0.00";
declared.getRange(`F5:F${4 + declaredRows.length}`).format.numberFormat = '"US$"#,##0.00';
declared.getRange(`H5:J${4 + declaredRows.length}`).format.fill = inputYellow;
declared.getRange(`A4:K${4 + declaredRows.length}`).format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
declared.getRange(`A5:K${4 + declaredRows.length}`).format = { verticalAlignment: "top", wrapText: true, rowHeight: 34 };
declared.getRange(`J5:J${4 + declaredRows.length}`).dataValidation = { rule: { type: "list", values: ["Pendiente", "Vinculado", "Justificado"] } };
[13, 19, 26, 10, 15, 15, 18, 30, 30, 14, 45].forEach((w, i) => declared.getRangeByIndexes(0, i, 4 + declaredRows.length, 1).format.columnWidth = w);
declared.freezePanes.freezeRows(4);

// Controles.
checks.showGridLines = false;
checks.mergeCells("A1:F1");
checks.getRange("A1").values = [["CONTROLES DE INTEGRIDAD"]];
checks.getRange("A1:F1").format = { fill: navy, font: { bold: true, color: "#FFFFFF", size: 15 }, horizontalAlignment: "center" };
checks.getRange("A3:F3").values = [["Control", "Actual", "Esperado", "Diferencia", "Tolerancia", "Estado"]];
checks.getRange("A3:F3").format = { fill: teal, font: { bold: true, color: "#FFFFFF" } };
checks.getRange("A4:A7").values = [["Debitos cuenta USD"], ["Creditos cuenta USD"], ["Debitos cuenta NIO"], ["Creditos cuenta NIO"]];
checks.getRange("B4").formulas = [["='Resumen'!E5"]];
checks.getRange("B5").formulas = [["='Resumen'!E6"]];
checks.getRange("B6").formulas = [["='Resumen'!E7"]];
checks.getRange("B7").formulas = [["='Resumen'!E8"]];
checks.getRange("C4:C7").values = [[5562.00], [6539.99], [27671.63], [28274.05]];
checks.getRange("D4").formulas = [["=B4-C4"]];
checks.getRange("D4:D7").fillDown();
checks.getRange("E4:E7").values = [[0.01], [0.01], [0.01], [0.01]];
checks.getRange("F4").formulas = [["=IF(ABS(D4)<=E4,\"OK\",\"REVISAR\")"]];
checks.getRange("F4:F7").fillDown();
checks.getRange("A9").values = [["ESTADO GENERAL"]];
checks.getRange("B9").formulas = [["=IF(COUNTIF(F4:F7,\"REVISAR\")=0,\"OK\",\"REVISAR\")"]];
checks.getRange("A3:F9").format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
checks.getRange("B4:E7").format.numberFormat = "#,##0.00";
checks.getRange("F4:F7").conditionalFormats.add("containsText", { text: "OK", format: { fill: green, font: { color: "#166534", bold: true } } });
checks.getRange("F4:F7").conditionalFormats.add("containsText", { text: "REVISAR", format: { fill: red, font: { color: "#991B1B", bold: true } } });
checks.getRange("A:A").format.columnWidth = 30;
checks.getRange("B:F").format.columnWidth = 16;

// Fuentes.
sources.showGridLines = false;
sources.getRange("A1:F1").values = [["Fuente", "Cuenta", "Moneda", "Periodo usado", "Fecha de revision", "Notas"]];
sources.getRange("A1:F1").format = { fill: navy, font: { bold: true, color: "#FFFFFF" } };
sources.getRange("A2:F3").values = [
  ["Movimientos northlink .pdf", "34232261", "USD", "2026-08-01 a 2026-08-24", new Date("2026-08-25T12:00:00"), "Movimientos extraidos del estado Banco LAFISE."],
  ["Movimientos_1787632141382.pdf", "39081312", "NIO", "2026-08-01 a 2026-08-19", new Date("2026-08-25T12:00:00"), "Movimientos extraidos del estado Banco LAFISE."],
];
sources.getRange("E2:E3").format.numberFormat = "yyyy-mm-dd";
sources.getRange("A1:F3").format.borders = { preset: "inside", style: "thin", color: "#D1D5DB" };
sources.getRange("A:A").format.columnWidth = 34;
sources.getRange("B:C").format.columnWidth = 18;
sources.getRange("D:D").format.columnWidth = 28;
sources.getRange("E:E").format.columnWidth = 18;
sources.getRange("F:F").format.columnWidth = 45;
sources.getRange("F2:F3").format.wrapText = true;

await fs.mkdir(outputDir, { recursive: true });

// Compact verification before export.
console.log((await wb.inspect({ kind: "table", range: `Controles!A1:F9`, include: "values,formulas", tableMaxRows: 12, tableMaxCols: 8 })).ndjson);
console.log((await wb.inspect({ kind: "match", searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A", options: { useRegex: true, maxResults: 100 }, summary: "formula error scan" })).ndjson);

for (const [sheetName, range, file] of [
  ["Resumen", `A1:E${12 + summaryCats.length}`, `${outputDir}/preview_resumen.png`],
  ["Movimientos", `A1:X22`, `${outputDir}/preview_movimientos.png`],
  ["Declarados fuera banco", `A1:K${4 + declaredRows.length}`, `${outputDir}/preview_declarados.png`],
  ["Catalogos", `A1:C${catRows + 1}`, `${outputDir}/preview_catalogos.png`],
  ["Controles", "A1:F9", `${outputDir}/preview_controles.png`],
  ["Fuentes", "A1:F3", `${outputDir}/preview_fuentes.png`],
]) {
  const preview = await wb.render({ sheetName, range, scale: 1, format: "png" });
  await fs.writeFile(file, new Uint8Array(await preview.arrayBuffer()));
}

const xlsx = await SpreadsheetFile.exportXlsx(wb);
await xlsx.save(outputPath);
console.log(JSON.stringify({ outputPath, rows: txs.length, firstRow, lastRow }));
