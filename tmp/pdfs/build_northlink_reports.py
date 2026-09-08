from pathlib import Path
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak,
    KeepTogether, HRFlowable
)
from reportlab.graphics.shapes import Drawing, Rect, String

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "output" / "pdf"
OUT.mkdir(parents=True, exist_ok=True)

RATE = 36.45
NAVY = colors.HexColor("#123047")
TEAL = colors.HexColor("#0F766E")
SKY = colors.HexColor("#E8F1F5")
PALE = colors.HexColor("#F4F7F9")
GOLD = colors.HexColor("#F4B942")
GREEN = colors.HexColor("#DDF5E8")
RED = colors.HexColor("#FCE5E5")
GRAY = colors.HexColor("#5B6573")
LIGHT_BORDER = colors.HexColor("#D7DEE3")

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name="ReportTitle", parent=styles["Title"], fontName="Helvetica-Bold", fontSize=21, leading=24, textColor=NAVY, spaceAfter=6))
styles.add(ParagraphStyle(name="Subtitle", parent=styles["Normal"], fontName="Helvetica", fontSize=10, leading=14, textColor=GRAY, spaceAfter=14))
styles.add(ParagraphStyle(name="H1x", parent=styles["Heading1"], fontName="Helvetica-Bold", fontSize=14, leading=18, textColor=NAVY, spaceBefore=10, spaceAfter=7))
styles.add(ParagraphStyle(name="H2x", parent=styles["Heading2"], fontName="Helvetica-Bold", fontSize=11, leading=14, textColor=TEAL, spaceBefore=7, spaceAfter=5))
styles.add(ParagraphStyle(name="Bodyx", parent=styles["BodyText"], fontName="Helvetica", fontSize=9.2, leading=13, textColor=colors.HexColor("#27313A"), spaceAfter=6))
styles.add(ParagraphStyle(name="Smallx", parent=styles["BodyText"], fontName="Helvetica", fontSize=7.6, leading=10, textColor=GRAY))
styles.add(ParagraphStyle(name="Tinyx", parent=styles["BodyText"], fontName="Helvetica", fontSize=6.7, leading=8, textColor=GRAY))
styles.add(ParagraphStyle(name="CompactH2", parent=styles["Heading2"], fontName="Helvetica-Bold", fontSize=10, leading=12, textColor=TEAL, spaceBefore=2, spaceAfter=2))
styles.add(ParagraphStyle(name="Callout", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=11, leading=15, textColor=NAVY, alignment=TA_CENTER))
styles.add(ParagraphStyle(name="Cell", parent=styles["BodyText"], fontName="Helvetica", fontSize=8.2, leading=10.5, textColor=colors.HexColor("#27313A")))
styles.add(ParagraphStyle(name="CellBold", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=8.2, leading=10.5, textColor=colors.HexColor("#27313A")))
styles.add(ParagraphStyle(name="CellRight", parent=styles["BodyText"], fontName="Helvetica", fontSize=8.2, leading=10.5, textColor=colors.HexColor("#27313A"), alignment=TA_RIGHT))
styles.add(ParagraphStyle(name="CellRightBold", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=8.2, leading=10.5, textColor=NAVY, alignment=TA_RIGHT))


def money(v):
    return f"US${v:,.2f}"


def cord(v):
    return f"C${v:,.2f}"


def P(text, style="Cell"):
    return Paragraph(str(text), styles[style])


def page_decor(canvas, doc):
    canvas.saveState()
    w, h = A4
    canvas.setFillColor(NAVY)
    canvas.rect(0, h - 13 * mm, w, 13 * mm, stroke=0, fill=1)
    canvas.setFillColor(colors.white)
    canvas.setFont("Helvetica-Bold", 9)
    canvas.drawString(18 * mm, h - 8.5 * mm, "NORTHLINK MICROSYSTEM")
    canvas.setFont("Helvetica", 7.5)
    canvas.drawRightString(w - 18 * mm, h - 8.5 * mm, "Reporte gerencial | Agosto 2026")
    canvas.setStrokeColor(LIGHT_BORDER)
    canvas.line(18 * mm, 14 * mm, w - 18 * mm, 14 * mm)
    canvas.setFillColor(GRAY)
    canvas.setFont("Helvetica", 7)
    canvas.drawString(18 * mm, 9.5 * mm, "Uso interno - cifras administrativas no auditadas")
    canvas.drawRightString(w - 18 * mm, 9.5 * mm, f"Pagina {doc.page}")
    canvas.restoreState()


def doc(path, title):
    return SimpleDocTemplate(
        str(path), pagesize=A4, rightMargin=18 * mm, leftMargin=18 * mm,
        topMargin=21 * mm, bottomMargin=19 * mm, title=title,
        author="Northlink Microsystem"
    )


def title_block(title, subtitle):
    return [Spacer(1, 5 * mm), Paragraph(title, styles["ReportTitle"]), Paragraph(subtitle, styles["Subtitle"]), HRFlowable(width="100%", thickness=1.2, color=TEAL, spaceAfter=10)]


def styled_table(rows, widths, header=True, totals=None, aligns=None, font_size=8.2):
    data = []
    for r_idx, row in enumerate(rows):
        rendered = []
        for c_idx, value in enumerate(row):
            if r_idx == 0 and header:
                rendered.append(P(value, "CellBold"))
            else:
                style = "CellRight" if aligns and aligns[c_idx] == "R" else "Cell"
                if totals and r_idx in totals:
                    style = "CellRightBold" if aligns and aligns[c_idx] == "R" else "CellBold"
                rendered.append(P(value, style))
        data.append(rendered)
    t = Table(data, colWidths=widths, repeatRows=1 if header else 0, hAlign="LEFT")
    cmd = [
        ("BACKGROUND", (0, 0), (-1, 0), TEAL if header else PALE),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white if header else NAVY),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ("LINEBELOW", (0, 0), (-1, -1), 0.35, LIGHT_BORDER),
    ]
    for r in totals or []:
        cmd.extend([
            ("BACKGROUND", (0, r), (-1, r), SKY),
            ("LINEABOVE", (0, r), (-1, r), 0.9, NAVY),
        ])
    if aligns:
        for c_idx, align in enumerate(aligns):
            cmd.append(("ALIGN", (c_idx, 1 if header else 0), (c_idx, -1), "RIGHT" if align == "R" else "LEFT"))
    t.setStyle(TableStyle(cmd))
    return t


def kpis(items):
    cells = []
    for label, value, color in items:
        cells.append(Table([[P(value, "Callout")], [P(label, "Smallx")]], colWidths=[52 * mm], rowHeights=[11 * mm, 10 * mm], style=TableStyle([
            ("BACKGROUND", (0, 0), (-1, -1), color),
            ("BOX", (0, 0), (-1, -1), 0.6, LIGHT_BORDER),
            ("ALIGN", (0, 0), (-1, -1), "CENTER"),
            ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
            ("LEFTPADDING", (0, 0), (-1, -1), 4),
            ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ])))
    return Table([cells], colWidths=[55 * mm] * len(cells), hAlign="LEFT", style=TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP")]))


def profit_bar(revenue, costs, profit):
    width = 165 * mm
    height = 24 * mm
    d = Drawing(width, height)
    bar_x, bar_y, bar_w, bar_h = 5 * mm, 8 * mm, 155 * mm, 7 * mm
    cost_w = bar_w * costs / revenue
    profit_w = bar_w * profit / revenue
    d.add(Rect(bar_x, bar_y, cost_w, bar_h, fillColor=GOLD, strokeColor=None))
    d.add(Rect(bar_x + cost_w, bar_y, profit_w, bar_h, fillColor=TEAL, strokeColor=None))
    d.add(String(bar_x, 18 * mm, f"Ventas {money(revenue)}", fontName="Helvetica-Bold", fontSize=9, fillColor=NAVY))
    d.add(String(bar_x, 3 * mm, f"Costos {money(costs)}", fontName="Helvetica", fontSize=7.5, fillColor=GRAY))
    d.add(String(bar_x + 93 * mm, 3 * mm, f"Ganancia {money(profit)}", fontName="Helvetica", fontSize=7.5, fillColor=TEAL))
    return d


def build_cash_report():
    path = OUT / "01_Reporte_Ingresos_Egresos_y_Caja_Agosto_2026.pdf"
    story = title_block("Reporte de ingresos, egresos y caja", "Conciliacion administrativa de agosto 2026 | Corte al 25 de agosto | Tasa de referencia C$36.45 por US$1")
    story += [kpis([
        ("Cobros de clientes del periodo", money(7370), SKY),
        ("Saldo bancario conciliado", money(1000), GREEN),
        ("Cuentas por cobrar totales", money(4920), colors.HexColor("#FFF3D6")),
    ]), Spacer(1, 9)]
    story += [Paragraph("1. Resumen ejecutivo", styles["H1x"]), Paragraph(
        "La caja principal queda conciliada. Entraron directamente a poder de la direccion US$6,780 y se identificaron salidas por US$5,780, dejando US$1,000 en la cuenta empresarial. Adicionalmente, US$590 cobrados a Yeris permanecen bajo custodia de Jairo y forman parte del efectivo de la empresa, aunque no de la caja principal.", styles["Bodyx"])]
    rows = [
        ["Ingreso recibido", "Monto", "Situacion"],
        ["Kevin - R & B Celulares", money(420), "Cobrado"],
        ["Rosidani - Vulcanizadora Rios", money(450), "Cobrado"],
        ["Don Hebbert", money(300), "Anticipo cobrado"],
        ["Lacteos Quebar", money(3450), "Anticipo cobrado"],
        ["Don Norvin", money(2160), "Anticipo cobrado"],
        ["Subtotal recibido por direccion", money(6780), "Base de conciliacion"],
        ["Yeris Altamirano", money(590), "Efectivo en custodia de Jairo"],
        ["Total cobrado por la empresa", money(7370), "Incluye custodia"],
    ]
    story += [Paragraph("2. Ingresos cobrados", styles["H1x"]), styled_table(rows, [82*mm, 34*mm, 49*mm], totals={6, 8}, aligns=["L", "R", "L"]), Spacer(1, 8)]
    out_rows = [
        ["Salida", "Monto USD", "Clasificacion"],
        ["Pago atrasado Greyvin", money(164.61), "Nomina de periodo anterior"],
        ["Pago atrasado Harrinton", money(82.30), "Nomina de periodo anterior"],
        ["Compensacion pendiente de formalizar - Jairo", money(754.46), "Bono/acuerdo interno no recuperable"],
        ["Pago atrasado Axel", money(123.46), "Nomina de periodo anterior"],
        ["Nomina quincenal del 15 de agosto", money(781.89), "Nomina corriente"],
        ["Pago en efectivo por contrato", money(10.97), "Gasto puntual"],
        ["Adelanto a Greyvin", money(68.59), "Adelanto salarial"],
        ["Caja chica", money(219.48), "Fondo por liquidar"],
        ["Equipos para proyectos", money(1778), "Costo directo de ventas"],
        ["Renta de modulo", money(500), "Gasto operativo"],
        ["Combustible", money(263), "Gasto operativo"],
        ["Viaticos", money(278), "Gasto operativo"],
        ["Prestamo a Axel", money(550), "Cuenta por cobrar"],
        ["Suscripciones", money(80), "Gasto operativo"],
        ["Nomina atrasada Jairo y Aaron", money(125.24), "Cierre residual de caja"],
        ["Total de salidas conciliadas", money(5780), "Cuadra con saldo final"],
    ]
    story += [Paragraph("3. Egresos y otras salidas", styles["H1x"]), styled_table(out_rows, [85*mm, 32*mm, 48*mm], totals={16}, aligns=["L", "R", "L"]), Spacer(1, 7)]
    story += [Paragraph("4. Conciliacion de caja", styles["H1x"]), styled_table([
        ["Concepto", "Monto"], ["Ingresos en poder de direccion", money(6780)], ["Salidas conciliadas", f"({money(5780)})"], ["Saldo esperado", money(1000)], ["Saldo informado en banco", money(1000)], ["Diferencia", money(0)]
    ], [115*mm, 50*mm], totals={3, 5}, aligns=["L", "R"])]
    story += [Paragraph("5. Capital rastreable y cartera", styles["H1x"]), styled_table([
        ["Recurso", "Monto", "Disponibilidad"],
        ["Cuenta empresarial", money(1000), "Inmediata"],
        ["Efectivo de Yeris en custodia", money(590), "Pendiente de entrega"],
        ["Prestamo a Axel", money(550), "Cuenta por cobrar"],
        ["Adelanto a Greyvin", money(68.59), "Descontable"],
        ["Caja chica", money(219.48), "Sujeta a liquidacion"],
        ["Capital rastreable", money(2428.07), "Antes de cartera de clientes"],
        ["Cuentas por cobrar", money(4920), "Sujetas a cumplimiento/cobro"],
        ["Capital bruto potencial", money(7348.07), "No equivale a utilidad"],
    ], [82*mm, 34*mm, 49*mm], totals={6, 8}, aligns=["L", "R", "L"])]
    story += [Spacer(1, 8), Paragraph("Conclusiones de control", styles["H1x"])]
    for text in [
        "La caja principal esta cuadrada en US$1,000.",
        "El efectivo de Yeris debe documentarse como fondo en custodia y entregarse a la empresa.",
        "Caja chica, adelantos y prestamos requieren recibo, responsable y fecha de liquidacion.",
        "La compensacion de Jairo debe formalizarse como bono o acuerdo por uso de vehiculo; no se considera activo recuperable.",
        "El saldo de Quebar usado en este reporte parte de un adelanto declarado de US$3,450."
    ]:
        story.append(Paragraph(f"- {text}", styles["Smallx"]))
    story += [Spacer(1, 3), Paragraph("Fuentes y alcance", styles["CompactH2"]), Paragraph(
        "Estados de movimientos Banco LAFISE de las cuentas USD y NIO, informacion declarada por la direccion y conciliacion realizada durante agosto de 2026. Este documento es un reporte administrativo interno y no sustituye estados financieros certificados.", styles["Smallx"])]
    doc(path, "Reporte de ingresos, egresos y caja").build(story, onFirstPage=page_decor, onLaterPages=page_decor)
    return path


def build_sales_report():
    path = OUT / "02_Reporte_Ventas_Cobranza_y_Comisiones_Agosto_2026.pdf"
    story = title_block("Reporte de ventas, cobranza y comisiones", "Produccion comercial, cartera y remuneracion variable | Agosto 2026")
    story += [kpis([
        ("Ventas cerradas", money(11940), SKY),
        ("Cobrado", money(7370), GREEN),
        ("Cartera total", money(4920), colors.HexColor("#FFF3D6")),
    ]), Spacer(1, 8)]
    sales = [
        ["Cliente/proyecto", "Contrato", "Cobrado", "Pendiente"],
        ["Kevin - R & B Celulares", money(420), money(420), money(0)],
        ["Rosidani - Vulcanizadora Rios", money(450), money(450), money(0)],
        ["Don Hebbert", money(510), money(300), money(210)],
        ["Lacteos Quebar", money(5900), money(3450), money(2450)],
        ["Don Norvin", money(3600), money(2160), money(1440)],
        ["Melissa Cisneros", money(470), money(0), money(470)],
        ["Yeris Altamirano", money(590), money(590), money(0)],
        ["Total ventas del periodo", money(11940), money(7370), money(4570)],
        ["Ferreteria Ruiz - cartera anterior", "-", "-", money(350)],
        ["Cartera total", "-", "-", money(4920)],
    ]
    story += [Paragraph("1. Ventas y cobranza", styles["H1x"]), styled_table(sales, [73*mm, 30*mm, 30*mm, 32*mm], totals={8, 10}, aligns=["L", "R", "R", "R"]), Spacer(1, 8)]
    story += [Paragraph("2. Produccion comercial", styles["H1x"]), Paragraph(
        "Greyvin participo en ventas por US$8,340 y Axel en US$6,350. Quebar y Rosidani fueron ventas compartidas; por eso estas cifras de participacion no deben sumarse entre si. Para medir produccion sin duplicar, las ventas compartidas se asignan 50/50.", styles["Bodyx"]), styled_table([
            ["Vendedor", "Ventas con participacion", "Cobrado asociado", "Atribucion sin duplicar"],
            ["Greyvin", money(8340), money(5210), money(5165)],
            ["Axel", money(6350), money(3900), money(3175)],
            ["Total unico atribuido", "-", money(5210), money(8340)],
        ], [48*mm, 42*mm, 38*mm, 37*mm], totals={3}, aligns=["L", "R", "R", "R"])]
    story += [PageBreak(), Paragraph("3. Comisiones devengadas", styles["H1x"])]
    greyvin = [
        ["Proyecto", "Base", "Tasa", "Comision"],
        ["Quebar", money(5900), "2.5%", money(147.50)],
        ["Rosidani", money(450), "3.0%", money(13.50)],
        ["Kevin", money(420), "6.0%", money(25.20)],
        ["Hebbert", money(510), "5.0%", money(25.50)],
        ["Melissa", money(470), "6.0%", money(28.20)],
        ["Yeris", money(590), "6.0%", money(35.40)],
        ["Total Greyvin", "-", "-", money(275.30)],
    ]
    axel = [
        ["Proyecto", "Base", "Tasa", "Comision"],
        ["Quebar", money(5900), "2.5%", money(147.50)],
        ["Rosidani", money(450), "3.0%", money(13.50)],
        ["Total Axel", "-", "-", money(161.00)],
    ]
    story += [Paragraph("Greyvin", styles["H2x"]), styled_table(greyvin, [70*mm, 36*mm, 26*mm, 33*mm], totals={7}, aligns=["L", "R", "R", "R"]), Spacer(1, 7), Paragraph("Axel", styles["H2x"]), styled_table(axel, [70*mm, 36*mm, 26*mm, 33*mm], totals={3}, aligns=["L", "R", "R", "R"])]
    story += [Paragraph("4. Comision pagable sobre cobranza", styles["H1x"]), styled_table([
        ["Vendedor", "Devengada total", "Pagable ahora", "Acumulada para cobros futuros"],
        ["Greyvin", money(275.30), money(175.35), money(99.95)],
        ["Axel", money(161.00), money(99.75), money(61.25)],
        ["Total", money(436.30), money(275.10), money(161.20)],
    ], [44*mm, 40*mm, 40*mm, 41*mm], totals={3}, aligns=["L", "R", "R", "R"])]
    story += [Spacer(1, 8), Paragraph("5. Pago bajo el modelo recomendado", styles["H1x"]), Paragraph(
        "La primera etapa propone una base mensual de C$12,000 para cada vendedor, mas la comision correspondiente al dinero cobrado. El adelanto de Greyvin se descuenta del pago de caja, sin reducir el costo salarial devengado.", styles["Bodyx"]), styled_table([
            ["Trabajador", "Base mensual", "Comision pagable", "Bruto", "Neto de adelanto"],
            ["Greyvin", cord(12000), cord(6391.51), cord(18391.51), cord(15891.51)],
            ["Axel", cord(12000), cord(3635.89), cord(15635.89), cord(15635.89)],
            ["Total", cord(24000), cord(10027.40), cord(34027.40), cord(31527.40)],
        ], [37*mm, 32*mm, 34*mm, 31*mm, 31*mm], totals={3}, aligns=["L", "R", "R", "R", "R"])]
    story += [PageBreak(), Paragraph("6. Politica de comisiones propuesta", styles["H1x"])]
    for text in [
        "La comision se genera por venta cerrada, pero se paga proporcionalmente al dinero cobrado.",
        "El saldo de comision no pagado se acumula automaticamente para el mes en que entre el cobro.",
        "Software, instalacion y servicios: hasta 6% de la venta neta.",
        "Equipos revendidos: 2% de la venta o 10% de la ganancia bruta, evitando comision alta sobre costos de terceros.",
        "Ventas compartidas: el fondo total sigue siendo 6%; se documenta la division antes del cierre.",
        "Asistencia de cierre: sale del mismo fondo de 6%, como en Quebar: 2.5% Greyvin, 2.5% Axel y 1% Harrinton.",
        "Descuentos, devoluciones e impuestos no generan comision.",
    ]:
        story.append(Paragraph(f"- {text}", styles["Bodyx"]))
    story += [Paragraph("Bonos sugeridos", styles["H2x"]), styled_table([
        ["Resultado individual cobrado", "Bono adicional"],
        ["Menos de US$2,000", "Sin bono"],
        ["US$2,000 a US$3,499", "C$1,000"],
        ["US$3,500 a US$4,999", "C$2,000"],
        ["US$5,000 o mas", "C$3,500"],
    ], [105*mm, 60*mm], aligns=["L", "R"]), Spacer(1, 8), Paragraph(
        "Nota laboral: las comisiones e incentivos forman parte del salario ordinario bajo el Codigo del Trabajo de Nicaragua. Deben documentarse, registrarse en planilla y considerarse para prestaciones conforme corresponda.", styles["Smallx"])]
    doc(path, "Reporte de ventas, cobranza y comisiones").build(story, onFirstPage=page_decor, onLaterPages=page_decor)
    return path


def build_management_report():
    path = OUT / "03_Analitica_Gerencial_y_Rumbo_del_Negocio_Agosto_2026.pdf"
    story = title_block("Analitica gerencial y rumbo del negocio", "Rentabilidad, estructura de costos, capital, salarios y prioridades de gestion | Agosto 2026")
    story += [kpis([
        ("Ventas cerradas", money(11940), SKY),
        ("Margen cargado actual", "52.1%", GREEN),
        ("Capital bruto potencial", money(7348.07), colors.HexColor("#FFF3D6")),
    ]), Spacer(1, 8)]
    story += [Paragraph("1. Veredicto ejecutivo", styles["H1x"]), Paragraph(
        "Northlink presenta una operacion rentable y comercialmente fuerte. Las ventas cerradas cubren ampliamente la estructura mensual; el reto principal no es el margen, sino convertir cartera en efectivo, construir reserva y formalizar salarios, comisiones y uso de vehiculos. La empresa puede mejorar remuneraciones de forma gradual sin comprometer su viabilidad.", styles["Bodyx"])]
    story += [Paragraph("2. Ventas, cobros y cartera", styles["H1x"]), styled_table([
        ["Indicador", "Monto", "Lectura gerencial"],
        ["Ventas cerradas del periodo", money(11940), "Base de rentabilidad devengada"],
        ["Cobrado", money(7370), "61.7% de las ventas"],
        ["Cartera del periodo", money(4570), "Pendiente de proyectos actuales"],
        ["Cartera anterior", money(350), "Ferreteria Ruiz"],
        ["Cartera total", money(4920), "41.2% de las ventas del periodo"],
    ], [70*mm, 35*mm, 60*mm], totals={5}, aligns=["L", "R", "L"])]
    story += [Paragraph("3. Estructura mensual de costos", styles["H1x"]), styled_table([
        ["Nivel de costo", "USD/mes", "Contenido"],
        ["Costo fijo minimo", money(2143.79), "Nomina base, renta y suscripciones"],
        ["Operacion normal", money(2684.79), "Agrega combustible y viaticos"],
        ["Operacion con comisiones devengadas", money(3121.09), "Sin cargas patronales ni reservas"],
        ["Operacion plenamente cargada actual", money(3924.45), "Incluye INSS, INATEC, aguinaldo y vacaciones"],
        ["Propuesta etapa 1", money(4078.27), "Nomina base C$61,000 mas comisiones completas"],
        ["Propuesta etapa 2", money(4232.09), "Nomina base C$65,000 mas comisiones completas"],
    ], [63*mm, 32*mm, 70*mm], totals={4}, aligns=["L", "R", "L"])]
    story += [PageBreak(), Paragraph("4. Rentabilidad plenamente cargada", styles["H1x"]), Paragraph(
        "El siguiente escenario reconoce salarios, comisiones, contribuciones patronales, reservas laborales, operacion normal, equipos y el gasto puntual de contrato. Excluye el pago de C$27,500 a Jairo del costo recurrente; se trata como compensacion extraordinaria pendiente de formalizar.", styles["Bodyx"]), styled_table([
            ["Concepto", "Actual", "Etapa 1", "Etapa 2"],
            ["Ventas", money(11940), money(11940), money(11940)],
            ["Operacion plenamente cargada", f"({money(3924.45)})", f"({money(4078.27)})", f"({money(4232.09)})"],
            ["Equipos", f"({money(1778)})", f"({money(1778)})", f"({money(1778)})"],
            ["Gasto puntual", f"({money(10.97)})", f"({money(10.97)})", f"({money(10.97)})"],
            ["Ganancia provisional", money(6226.58), money(6072.76), money(5918.94)],
            ["Margen", "52.1%", "50.9%", "49.6%"],
        ], [68*mm, 32*mm, 32*mm, 33*mm], totals={5, 6}, aligns=["L", "R", "R", "R"]), Spacer(1, 5), profit_bar(11940, 6021.06, 5918.94)]
    story += [Paragraph("5. Evaluacion de salarios", styles["H1x"]), styled_table([
        ["Etapa", "Condicion", "Decision"],
        ["Inmediata", "Caja conciliada y cobros actuales cubren costos", "Subir Greyvin y Axel a C$12,000 mensuales"],
        ["Etapa 2", "Dos meses cobrando al menos US$6,000 y reserva de US$8,000", "Subirlos a C$13,500"],
        ["Participacion de utilidades", "Reserva de tres meses y cobranza superior a US$10,000", "Hasta 2% de utilidad operativa para el equipo"],
    ], [35*mm, 72*mm, 58*mm], aligns=["L", "L", "L"])]
    story += [PageBreak(), Paragraph("6. Organigrama de remuneracion recomendado", styles["H1x"]), styled_table([
        ["Rol", "Base sugerida", "Variable"],
        ["Direccion general - Harrinton", "C$14,000", "1% solo en cierres con asistencia directa"],
        ["Ventas - Greyvin", "C$12,000 inicial", "6% individual o parte documentada del fondo compartido"],
        ["Ventas/soporte - Axel", "C$12,000 inicial", "6% individual o parte documentada del fondo compartido"],
        ["Operaciones/logistica - Jairo", "C$12,000", "Bono por instalacion; vehiculo en acuerdo separado"],
        ["Administracion/finanzas - Aaron", "C$12,000", "1% solo sobre cartera vencida recuperada"],
    ], [58*mm, 34*mm, 73*mm], aligns=["L", "R", "L"])]
    story += [Paragraph("7. Capital y reserva", styles["H1x"]), styled_table([
        ["Indicador", "Monto", "Objetivo"],
        ["Liquidez inmediata", money(1000), "Cubrir obligaciones de corto plazo"],
        ["Capital rastreable", money(2428.07), "Incluye custodia, prestamos, adelantos y caja chica"],
        ["Capital bruto potencial", money(7348.07), "Incluye cuentas por cobrar"],
        ["Reserva minima recomendada", money(10862), "Tres meses de operacion fija propuesta"],
        ["Brecha de reserva", money(3513.93), "Debe financiarse con cobranza y utilidad retenida"],
    ], [66*mm, 35*mm, 64*mm], totals={4, 5}, aligns=["L", "R", "L"])]
    story += [Paragraph("8. Prioridades de los proximos 60 dias", styles["H1x"])]
    priorities = [
        "Cobrar US$2,450 de Quebar y US$1,440 de Norvin conforme a entregables.",
        "Completar Melissa y convertir US$470 firmados en cobro real.",
        "Recuperar US$350 de Ferreteria Ruiz y US$210 de Hebbert.",
        "Adoptar la comision sobre cobranza y separar software de equipos.",
        "Formalizar el acuerdo de vehiculo de Jairo, con monto, alcance y comprobacion.",
        "Crear una reserva separada para INSS, INATEC, aguinaldo y vacaciones.",
        "Retener utilidad hasta alcanzar al menos US$8,000; meta final de reserva US$10,862.",
    ]
    for idx, text in enumerate(priorities, 1):
        story.append(Paragraph(f"{idx}. {text}", styles["Tinyx"]))
    story += [Spacer(1, 5), Paragraph("9. Riesgos y controles", styles["H1x"]), styled_table([
        ["Riesgo", "Control recomendado"],
        ["Comisiones sobre contratos no cobrados", "Pago proporcional a cobranza; saldo acumulado"],
        ["Margen bajo en hardware", "Comision de 2% sobre venta o 10% de ganancia bruta"],
        ["Caja dispersa entre personas", "Custodio, recibo, fecha de liquidacion y comprobante"],
        ["Pasivo laboral subestimado", "Provision mensual de INSS, INATEC, aguinaldo y vacaciones"],
        ["Crecimiento sin reserva", "No elevar bases a etapa 2 antes de cumplir metas de caja"],
    ], [72*mm, 93*mm], aligns=["L", "L"])]
    story += [Spacer(1, 2), Paragraph("Referencias legales utilizadas", styles["CompactH2"]), Paragraph(
        "Codigo del Trabajo de Nicaragua: salario ordinario incluye salario basico, incentivos y comisiones; el contrato debe definir la forma de remuneracion. INSS: aporte patronal publicado de 21.5% para empleadores con menos de 50 trabajadores en Regimen Integral. INATEC: aporte obligatorio del 2% sobre planilla. Verificar con contador o MITRAB la clasificacion sectorial y el salario minimo aplicable antes de formalizar cambios. Enlaces: <link href='https://legislacion.asamblea.gob.ni/Normaweb.nsf/%28%24All%29/FA251B3C54F5BAEF062571C40055736C'>Codigo del Trabajo</link> | <link href='https://inss-princ.inss.gob.ni/index.php/tramites-37/10-afiliaciones/13-regimenes-de-afiliacion'>INSS</link> | <link href='https://legislacion.asamblea.gob.ni/gacetas/2021/2/g35.pdf'>INATEC</link>", styles["Tinyx"])]
    doc(path, "Analitica gerencial y rumbo del negocio").build(story, onFirstPage=page_decor, onLaterPages=page_decor)
    return path


if __name__ == "__main__":
    paths = [build_cash_report(), build_sales_report(), build_management_report()]
    for p in paths:
        print(p)
