import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { formatCurrency, formatDate, COMPANY_INFO } from './data';

export function generatePDF(quotation, companySettings = {}, logoDataUrl = null) {
  const company = { ...COMPANY_INFO, ...companySettings };
  const doc = new jsPDF('p', 'mm', 'a4');
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 15;
  const contentWidth = pageWidth - margin * 2;
  let y = margin;

  const primaryColor = [37, 99, 235];
  const darkColor = [15, 23, 42];
  const grayColor = [100, 116, 139];
  const lightGray = [241, 245, 249];
  const isIntraState = (quotation.gstType || 'cgst_sgst') === 'cgst_sgst';

  // Draft watermark
  if (quotation.status === 'draft') {
    doc.setFontSize(60);
    doc.setTextColor(230, 230, 230);
    doc.text('DRAFT', pageWidth / 2, pageHeight / 2, { align: 'center', angle: 45 });
  }

  // ===== HEADER =====
  doc.setFillColor(...primaryColor);
  doc.rect(0, 0, pageWidth, 42, 'F');

  let textStartX = margin;
  if (logoDataUrl) {
    try {
      const props = doc.getImageProperties(logoDataUrl);
      const maxH = 26, maxW = 50;
      const ratio = Math.min(maxW / props.width, maxH / props.height);
      const w = props.width * ratio, h = props.height * ratio;
      doc.addImage(logoDataUrl, 'PNG', margin, 8 + (maxH - h) / 2, w, h);
      textStartX = margin + w + 6;
    } catch (e) { console.warn('Logo error'); }
  }

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(22);
  doc.setTextColor(255, 255, 255);
  doc.text(company.name, textStartX, 18);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  doc.setTextColor(200, 210, 254);
  doc.text(company.tagline || 'Your Trusted IT Partner', textStartX, 24);

  doc.setFontSize(8);
  const contactLines = [];
  if (company.phone) contactLines.push(`Ph: ${company.phone}`);
  if (company.email) contactLines.push(`Email: ${company.email}`);
  if (company.website) contactLines.push(`Web: ${company.website}`);
  contactLines.forEach((line, i) => doc.text(line, pageWidth - margin, 14 + i * 5, { align: 'right' }));
  if (company.address) {
    doc.setFontSize(7);
    doc.text(company.address, pageWidth - margin, 14 + contactLines.length * 5, { align: 'right' });
  }

  y = 50;

  // ===== TITLE =====
  doc.setFillColor(...lightGray);
  doc.roundedRect(margin, y, contentWidth, 12, 2, 2, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(...darkColor);
  doc.text('QUOTATION', pageWidth / 2, y + 8.5, { align: 'center' });
  y += 18;

  // ===== INFO BOXES =====
  const halfWidth = contentWidth / 2 - 4;

  // Left: Quotation details
  doc.setFillColor(248, 250, 252);
  doc.roundedRect(margin, y, halfWidth, 36, 2, 2, 'F');
  doc.setDrawColor(226, 232, 240);
  doc.roundedRect(margin, y, halfWidth, 36, 2, 2, 'S');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(...primaryColor);
  doc.text('Quotation Details', margin + 4, y + 6);

  const qDetails = [
    ['Quotation No:', quotation.quotationNumber],
    ['Date:', formatDate(quotation.date)],
    ['Valid Until:', formatDate(getValidUntilDate(quotation.date, quotation.validityDays))],
  ];
  if (company.gstin) qDetails.push(['GSTIN:', company.gstin]);

  doc.setFontSize(8.5);
  qDetails.forEach(([label, value], i) => {
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...grayColor);
    doc.text(label, margin + 4, y + 12 + i * 5.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(...darkColor);
    doc.text(value, margin + 30, y + 12 + i * 5.5);
  });

  // Right: Client
  const rightX = margin + halfWidth + 8;
  doc.setFillColor(248, 250, 252);
  doc.roundedRect(rightX, y, halfWidth, 36, 2, 2, 'F');
  doc.setDrawColor(226, 232, 240);
  doc.roundedRect(rightX, y, halfWidth, 36, 2, 2, 'S');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(...primaryColor);
  doc.text('Bill To', rightX + 4, y + 6);

  doc.setFontSize(8.5);
  doc.setTextColor(...darkColor);
  const cDetails = [];
  if (quotation.client.name) cDetails.push(quotation.client.name);
  if (quotation.client.company) cDetails.push(quotation.client.company);
  if (quotation.client.phone) cDetails.push(`Ph: ${quotation.client.phone}`);
  if (quotation.client.email) cDetails.push(`Email: ${quotation.client.email}`);
  if (quotation.client.address) cDetails.push(quotation.client.address);
  if (quotation.client.gstin) cDetails.push(`GSTIN: ${quotation.client.gstin}`);

  cDetails.forEach((line, i) => {
    doc.setFont('helvetica', i === 0 ? 'bold' : 'normal');
    doc.text(line, rightX + 4, y + 12 + i * 5, { maxWidth: halfWidth - 8 });
  });

  y += 42;

  // ===== TABLE =====
  const hasHSN = quotation.items.some(i => i.hsnCode);
  const headRow = hasHSN
    ? ['#', 'Item / Description', 'HSN/SAC', 'Unit', 'Qty', 'Rate', 'Amount']
    : ['#', 'Item / Description', 'Unit', 'Qty', 'Rate', 'Amount'];

  const tableBody = quotation.items.map((item, index) => {
    const row = [
      (index + 1).toString(),
      item.name + (item.description ? `\n${item.description}` : ''),
    ];
    if (hasHSN) row.push(item.hsnCode || '-');
    row.push((item.unit || 'Nos').charAt(0).toUpperCase() + (item.unit || 'nos').slice(1));
    row.push(item.quantity.toString());
    row.push(formatCurrency(item.price));
    row.push(formatCurrency(item.quantity * item.price));
    return row;
  });

  const colStyles = hasHSN
    ? { 0: { cellWidth: 8, halign: 'center' }, 1: { cellWidth: 'auto' }, 2: { cellWidth: 18, halign: 'center' }, 3: { cellWidth: 14, halign: 'center' }, 4: { cellWidth: 12, halign: 'center' }, 5: { cellWidth: 25, halign: 'right' }, 6: { cellWidth: 28, halign: 'right' } }
    : { 0: { cellWidth: 8, halign: 'center' }, 1: { cellWidth: 'auto' }, 2: { cellWidth: 14, halign: 'center' }, 3: { cellWidth: 14, halign: 'center' }, 4: { cellWidth: 28, halign: 'right' }, 5: { cellWidth: 30, halign: 'right' } };

  autoTable(doc, {
    startY: y,
    head: [headRow],
    body: tableBody,
    theme: 'grid',
    margin: { left: margin, right: margin },
    styles: { fontSize: 8, cellPadding: 3.5, lineColor: [226, 232, 240], lineWidth: 0.3, textColor: darkColor },
    headStyles: { fillColor: primaryColor, textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 8.5, halign: 'center' },
    columnStyles: colStyles,
    alternateRowStyles: { fillColor: [248, 250, 252] },
    didDrawPage: (data) => {
      // Re-add header on new pages
      if (data.pageNumber > 1) {
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, pageWidth, 12, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(255, 255, 255);
        doc.text(`${company.name} — ${quotation.quotationNumber}`, margin, 8);
      }
    },
  });

  y = doc.lastAutoTable.finalY + 6;

  // ===== TOTALS =====
  const totalsX = pageWidth - margin - 78;
  const subtotal = quotation.items.reduce((sum, item) => sum + item.quantity * item.price, 0);
  let discountAmount = 0;
  if (quotation.discountType === 'percent') discountAmount = (subtotal * quotation.discount) / 100;
  else discountAmount = quotation.discount || 0;
  const afterDiscount = subtotal - discountAmount;
  const gstRate = quotation.gstRate || 0;
  const gstAmount = quotation.gstEnabled ? (afterDiscount * gstRate) / 100 : 0;
  const grandTotal = afterDiscount + gstAmount;

  let totalLines = 1; // subtotal
  if (discountAmount > 0) totalLines++;
  if (quotation.gstEnabled) totalLines += isIntraState ? 2 : 1;
  totalLines++; // grand total
  const totalsHeight = 6 + totalLines * 6 + 4;

  // Check page space
  if (y + totalsHeight + 60 > pageHeight) { doc.addPage(); y = margin + 15; }

  doc.setFillColor(248, 250, 252);
  doc.roundedRect(totalsX - 4, y, 82, totalsHeight, 2, 2, 'F');

  let ty = y + 6;
  const drawLine = (label, value, bold = false) => {
    doc.setFont('helvetica', bold ? 'bold' : 'normal');
    doc.setFontSize(bold ? 10 : 8.5);
    doc.setTextColor(...(bold ? darkColor : grayColor));
    doc.text(label, totalsX, ty);
    doc.setTextColor(...darkColor);
    doc.text(value, pageWidth - margin, ty, { align: 'right' });
    ty += bold ? 7 : 5.5;
  };

  drawLine('Subtotal:', formatCurrency(subtotal));
  if (discountAmount > 0) {
    drawLine(quotation.discountType === 'percent' ? `Discount (${quotation.discount}%):` : 'Discount:', `- ${formatCurrency(discountAmount)}`);
  }
  if (quotation.gstEnabled) {
    if (isIntraState) {
      drawLine(`CGST (${gstRate / 2}%):`, formatCurrency(gstAmount / 2));
      drawLine(`SGST (${gstRate / 2}%):`, formatCurrency(gstAmount / 2));
    } else {
      drawLine(`IGST (${gstRate}%):`, formatCurrency(gstAmount));
    }
  }

  doc.setDrawColor(...primaryColor);
  doc.setLineWidth(0.5);
  doc.line(totalsX, ty - 2, pageWidth - margin, ty - 2);
  ty += 2;
  drawLine('Grand Total:', formatCurrency(grandTotal), true);

  // Amount in words
  doc.setFont('helvetica', 'italic');
  doc.setFontSize(8);
  doc.setTextColor(...grayColor);
  doc.text(`Amount in words: ${numberToWords(Math.round(grandTotal))} Rupees Only`, margin, y + 6, { maxWidth: totalsX - margin - 10 });

  y = ty + 8;
  if (y > pageHeight - 80) { doc.addPage(); y = margin + 15; }

  // ===== BANK DETAILS =====
  if (company.bankName) {
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(...primaryColor);
    doc.text('Bank Details for Payment', margin, y);
    y += 5;

    doc.setFillColor(248, 250, 252);
    doc.roundedRect(margin, y - 2, contentWidth / 2, 22, 2, 2, 'F');

    doc.setFontSize(8);
    const bankLines = [`Bank: ${company.bankName}`];
    if (company.bankAccountNo) bankLines.push(`A/C No: ${company.bankAccountNo}`);
    if (company.bankIfsc) bankLines.push(`IFSC: ${company.bankIfsc}`);
    if (company.bankBranch) bankLines.push(`Branch: ${company.bankBranch}`);

    bankLines.forEach((line, i) => {
      doc.setFont('helvetica', 'normal');
      doc.setTextColor(...darkColor);
      doc.text(line, margin + 4, y + 3 + i * 4.5);
    });
    y += 26;
  }

  // ===== NOTES =====
  if (quotation.notes) {
    if (y > pageHeight - 50) { doc.addPage(); y = margin + 15; }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(...primaryColor);
    doc.text('Notes:', margin, y);
    y += 5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(...grayColor);
    const noteLines = doc.splitTextToSize(quotation.notes, contentWidth);
    doc.text(noteLines, margin, y);
    y += noteLines.length * 4 + 4;
  }

  // ===== TERMS =====
  if (quotation.termsAndConditions) {
    if (y > pageHeight - 50) { doc.addPage(); y = margin + 15; }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(...primaryColor);
    doc.text('Terms & Conditions:', margin, y);
    y += 5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(...grayColor);
    const termLines = doc.splitTextToSize(quotation.termsAndConditions, contentWidth);
    doc.text(termLines, margin, y);
    y += termLines.length * 3.5 + 6;
  }

  // ===== SIGNATURE =====
  if (y > pageHeight - 45) { doc.addPage(); y = margin + 15; }
  const sigX = pageWidth - margin - 55;
  if (company.signatureDataUrl) {
    try {
      const props = doc.getImageProperties(company.signatureDataUrl);
      const maxW = 45, maxH = 18;
      const r = Math.min(maxW / props.width, maxH / props.height);
      doc.addImage(company.signatureDataUrl, 'PNG', sigX, y, props.width * r, props.height * r);
      y += 20;
    } catch (e) { y += 4; }
  } else {
    y += 4;
  }
  doc.setDrawColor(...grayColor);
  doc.setLineWidth(0.3);
  doc.line(sigX, y, sigX + 50, y);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(...grayColor);
  doc.text('Authorized Signatory', sigX + 25, y + 5, { align: 'center' });
  doc.setFontSize(7);
  doc.text(company.name, sigX + 25, y + 9, { align: 'center' });

  // ===== FOOTER =====
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    const footerY = pageHeight - 16;
    doc.setDrawColor(...primaryColor);
    doc.setLineWidth(0.5);
    doc.line(margin, footerY - 4, pageWidth - margin, footerY - 4);
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(8);
    doc.setTextColor(...grayColor);
    doc.text(company.footerNote || 'Thank you for your business!', pageWidth / 2, footerY, { align: 'center' });
    doc.setFontSize(7);
    doc.text(`Generated by ${company.name} | ${company.phone || ''} | ${company.email || ''}`, pageWidth / 2, footerY + 5, { align: 'center' });
    doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 8, { align: 'right' });
  }

  return doc;
}

function getValidUntilDate(dateStr, days) {
  const date = new Date(dateStr);
  date.setDate(date.getDate() + days);
  return date.toISOString();
}

function numberToWords(num) {
  if (num === 0) return 'Zero';
  const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
    'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
  const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

  function convertBelow1000(n) {
    if (n === 0) return '';
    if (n < 20) return ones[n];
    if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
    return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' and ' + convertBelow1000(n % 100) : '');
  }

  if (num < 1000) return convertBelow1000(num);
  let result = '';
  if (num >= 10000000) { result += convertBelow1000(Math.floor(num / 10000000)) + ' Crore '; num %= 10000000; }
  if (num >= 100000) { result += convertBelow1000(Math.floor(num / 100000)) + ' Lakh '; num %= 100000; }
  if (num >= 1000) { result += convertBelow1000(Math.floor(num / 1000)) + ' Thousand '; num %= 1000; }
  if (num > 0) result += convertBelow1000(num);
  return result.trim();
}

export function downloadPDF(quotation, companySettings, logoDataUrl) {
  const doc = generatePDF(quotation, companySettings, logoDataUrl);
  doc.save(`Quotation-${quotation.quotationNumber}.pdf`);
}

export function shareViaWhatsApp(quotation, companySettings) {
  const company = { ...COMPANY_INFO, ...companySettings };
  const subtotal = quotation.items.reduce((sum, item) => sum + item.quantity * item.price, 0);
  let discountAmount = 0;
  if (quotation.discountType === 'percent') discountAmount = (subtotal * quotation.discount) / 100;
  else discountAmount = quotation.discount || 0;
  const afterDiscount = subtotal - discountAmount;
  const gstAmount = quotation.gstEnabled ? (afterDiscount * quotation.gstRate) / 100 : 0;
  const grandTotal = afterDiscount + gstAmount;

  let message = `*QUOTATION - ${company.name}*\n━━━━━━━━━━━━━━━\n`;
  message += `Quotation No: *${quotation.quotationNumber}*\nDate: ${formatDate(quotation.date)}\nValid for: ${quotation.validityDays} days\n\n`;
  if (quotation.client.name) {
    message += `*Client:* ${quotation.client.name}\n`;
    if (quotation.client.company) message += `${quotation.client.company}\n`;
    message += `\n`;
  }
  message += `*Items:*\n`;
  quotation.items.forEach((item, i) => {
    message += `${i + 1}. ${item.name} — ${item.quantity} x ${formatCurrency(item.price)} = ${formatCurrency(item.quantity * item.price)}\n`;
  });
  message += `\n━━━━━━━━━━━━━━━\n*Grand Total: ${formatCurrency(grandTotal)}*\n`;
  if (quotation.gstEnabled) message += `(Inclusive of ${quotation.gstRate}% GST)\n`;
  message += `━━━━━━━━━━━━━━━\n\n`;
  message += `${company.phone || ''}\n${company.email || ''}\n\n`;
  message += `_${company.footerNote || 'Thank you for your business!'}_`;

  const phone = quotation.client.phone ? quotation.client.phone.replace(/[^0-9]/g, '') : '';
  const url = phone ? `https://wa.me/91${phone}?text=${encodeURIComponent(message)}` : `https://wa.me/?text=${encodeURIComponent(message)}`;
  window.open(url, '_blank');
}
