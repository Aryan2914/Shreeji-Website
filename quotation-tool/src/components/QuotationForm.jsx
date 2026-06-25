import { useState, useEffect, useMemo, useCallback, useRef } from 'react';
import {
  FiArrowLeft, FiSave, FiDownload, FiPlus, FiTrash2,
  FiMove, FiCheck
} from 'react-icons/fi';
import { FaWhatsapp } from 'react-icons/fa';
import {
  CATEGORIES, VALIDITY_OPTIONS, UNITS, GST_TYPES, TERMS_TEMPLATES,
  createEmptyItem, formatCurrency, formatDate, getItemSuggestions
} from '../data';
import TemplatePicker from './TemplatePicker';
import { downloadPDF, shareViaWhatsApp } from '../pdfGenerator';
import { loadSettings } from '../storage';
import toast from 'react-hot-toast';

function QuotationForm({ quotation: initialQuotation, onSave, onBack, settings }) {
  const [quotation, setQuotation] = useState(initialQuotation);
  const [showTemplates, setShowTemplates] = useState(false);
  const [autoSaved, setAutoSaved] = useState(false);
  const [dragIndex, setDragIndex] = useState(null);
  const [activeSuggestion, setActiveSuggestion] = useState(null);
  const autoSaveTimer = useRef(null);

  useEffect(() => {
    setQuotation(initialQuotation);
  }, [initialQuotation]);

  // Auto-save with debounce
  useEffect(() => {
    if (!quotation || !quotation.client) return;
    if (autoSaveTimer.current) clearTimeout(autoSaveTimer.current);
    autoSaveTimer.current = setTimeout(() => {
      onSave(quotation, true);
      setAutoSaved(true);
      setTimeout(() => setAutoSaved(false), 2000);
    }, 5000);
    return () => clearTimeout(autoSaveTimer.current);
  }, [quotation]);

  // Keyboard shortcuts
  useEffect(() => {
    const handler = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        handleSave();
      }
      if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        handleDownloadPDF();
      }
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [quotation]);

  const updateField = (field, value) => {
    setQuotation(prev => ({ ...prev, [field]: value }));
  };

  const updateClient = (field, value) => {
    setQuotation(prev => ({
      ...prev,
      client: { ...prev.client, [field]: value },
    }));
  };

  const updateItem = (index, field, value) => {
    setQuotation(prev => {
      const items = [...prev.items];
      items[index] = { ...items[index], [field]: value };
      return { ...prev, items };
    });
  };

  const addItem = () => {
    setQuotation(prev => ({
      ...prev,
      items: [...prev.items, createEmptyItem()],
    }));
  };

  const removeItem = (index) => {
    if (quotation.items.length <= 1) return;
    setQuotation(prev => ({
      ...prev,
      items: prev.items.filter((_, i) => i !== index),
    }));
  };

  // Drag reorder
  const handleDragStart = (index) => setDragIndex(index);
  const handleDragOver = (e, index) => {
    e.preventDefault();
    if (dragIndex === null || dragIndex === index) return;
    setQuotation(prev => {
      const items = [...prev.items];
      const [moved] = items.splice(dragIndex, 1);
      items.splice(index, 0, moved);
      return { ...prev, items };
    });
    setDragIndex(index);
  };
  const handleDragEnd = () => setDragIndex(null);

  // Apply suggestion from history
  const applySuggestion = (index, suggestion) => {
    setQuotation(prev => {
      const items = [...prev.items];
      items[index] = {
        ...items[index],
        name: suggestion.name,
        description: suggestion.description,
        category: suggestion.category,
        price: suggestion.price,
        unit: suggestion.unit || 'nos',
        hsnCode: suggestion.hsnCode || '',
      };
      return { ...prev, items };
    });
    setActiveSuggestion(null);
  };

  const applyTemplate = (template) => {
    const newItems = template.items.map(item => ({
      ...createEmptyItem(),
      ...item,
    }));
    setQuotation(prev => ({
      ...prev,
      items: [...prev.items.filter(i => i.name), ...newItems],
    }));
    setShowTemplates(false);
  };

  const applyTermsTemplate = (templateId) => {
    const t = TERMS_TEMPLATES.find(x => x.id === templateId);
    if (t) updateField('termsAndConditions', t.text);
  };

  // Calculations
  const calculations = useMemo(() => {
    const subtotal = quotation.items.reduce(
      (sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.price) || 0), 0
    );
    let discountAmount = 0;
    if (quotation.discountType === 'percent') {
      discountAmount = (subtotal * (parseFloat(quotation.discount) || 0)) / 100;
    } else {
      discountAmount = parseFloat(quotation.discount) || 0;
    }
    const afterDiscount = subtotal - discountAmount;
    const gstRate = parseFloat(quotation.gstRate) || 0;
    const gstAmount = quotation.gstEnabled ? (afterDiscount * gstRate) / 100 : 0;
    const grandTotal = afterDiscount + gstAmount;

    // Split GST
    const isIntraState = (quotation.gstType || 'cgst_sgst') === 'cgst_sgst';
    const cgst = isIntraState ? gstAmount / 2 : 0;
    const sgst = isIntraState ? gstAmount / 2 : 0;
    const igst = isIntraState ? 0 : gstAmount;

    return { subtotal, discountAmount, afterDiscount, gstAmount, grandTotal, cgst, sgst, igst, isIntraState };
  }, [quotation.items, quotation.discount, quotation.discountType, quotation.gstEnabled, quotation.gstRate, quotation.gstType]);

  const getCompanySettings = () => {
    const s = settings || loadSettings() || {};
    return {
      name: s.companyName, tagline: s.tagline, address: s.address,
      phone: s.phone, email: s.email, website: s.website, gstin: s.gstin,
      footerNote: s.footerNote, bankName: s.bankName, bankAccountNo: s.bankAccountNo,
      bankIfsc: s.bankIfsc, bankBranch: s.bankBranch, signatureDataUrl: s.signatureDataUrl,
    };
  };

  const handleDownloadPDF = () => {
    const s = settings || loadSettings() || {};
    downloadPDF(quotation, getCompanySettings(), s.logoDataUrl || null);
  };

  const handleWhatsApp = () => shareViaWhatsApp(quotation, getCompanySettings());

  const handleSave = () => {
    if (autoSaveTimer.current) clearTimeout(autoSaveTimer.current);
    onSave(quotation);
  };

  if (!quotation) return null;

  const suggestions = getItemSuggestions();

  return (
    <div className="p-4 lg:p-8 max-w-7xl mx-auto animate-in text-surface-800">
      {/* Header */}
      <div className="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-surface-200">
        <div className="flex items-center gap-4">
          <button onClick={onBack} className="p-2 rounded-lg hover:bg-surface-100 text-surface-500 hover:text-surface-800 transition-all">
            <FiArrowLeft className="text-xl" />
          </button>
          <div>
            <h2 className="text-2xl font-semibold text-surface-900">{quotation.quotationNumber}</h2>
            <p className="text-surface-500 text-sm">
              Created {formatDate(quotation.createdAt)}
              {autoSaved && <span className="ml-2 text-emerald-600 text-xs font-medium">✓ Auto-saved</span>}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <span className="hidden sm:inline text-[10px] text-surface-400">Ctrl+S Save • Ctrl+P PDF</span>
          <select value={quotation.status} onChange={(e) => updateField('status', e.target.value)} className="select-field w-auto text-sm bg-white">
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
          </select>
          <button onClick={handleSave} className="btn-primary text-sm shadow-sm">
            <FiSave /> Save
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left Column */}
        <div className="lg:col-span-2 space-y-6">

          {/* Quotation Details */}
          <div className="bg-white rounded-xl shadow-sm border border-surface-200 overflow-hidden">
            <div className="bg-surface-50 px-6 py-4 border-b border-surface-200">
              <h3 className="font-semibold text-surface-800">Quotation Details</h3>
            </div>
            <div className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                <div>
                  <label className="form-label">Quotation No.</label>
                  <input type="text" value={quotation.quotationNumber} onChange={(e) => updateField('quotationNumber', e.target.value)} className="input-field font-mono" />
                </div>
                <div>
                  <label className="form-label">Date</label>
                  <input type="date" value={quotation.date} onChange={(e) => updateField('date', e.target.value)} className="input-field" />
                </div>
                <div>
                  <label className="form-label">Validity</label>
                  <select value={quotation.validityDays} onChange={(e) => updateField('validityDays', parseInt(e.target.value))} className="select-field">
                    {VALIDITY_OPTIONS.map(opt => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
              </div>

              <h4 className="font-medium text-surface-800 mb-4 pb-2 border-b border-surface-100">Client Information</h4>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                  <label className="form-label">Client Name *</label>
                  <input type="text" value={quotation.client.name} onChange={(e) => updateClient('name', e.target.value)} placeholder="e.g. Rajesh Patel" className="input-field" />
                </div>
                <div>
                  <label className="form-label">Company</label>
                  <input type="text" value={quotation.client.company} onChange={(e) => updateClient('company', e.target.value)} placeholder="e.g. ABC Enterprises" className="input-field" />
                </div>
                <div>
                  <label className="form-label">Phone</label>
                  <input type="tel" value={quotation.client.phone} onChange={(e) => updateClient('phone', e.target.value)} placeholder="e.g. 9876543210" className="input-field" />
                </div>
                <div>
                  <label className="form-label">Email</label>
                  <input type="email" value={quotation.client.email} onChange={(e) => updateClient('email', e.target.value)} placeholder="e.g. client@example.com" className="input-field" />
                </div>
                <div>
                  <label className="form-label">Address</label>
                  <input type="text" value={quotation.client.address} onChange={(e) => updateClient('address', e.target.value)} placeholder="e.g. 123 Main Street, Ahmedabad" className="input-field" />
                </div>
                <div>
                  <label className="form-label">Client GSTIN</label>
                  <input type="text" value={quotation.client.gstin || ''} onChange={(e) => updateClient('gstin', e.target.value.toUpperCase())} placeholder="Optional" className="input-field" maxLength={15} />
                </div>
              </div>
            </div>
          </div>

          {/* Line Items */}
          <div className="bg-white rounded-xl shadow-sm border border-surface-200 overflow-hidden">
            <div className="bg-surface-50 px-6 py-4 border-b border-surface-200 flex items-center justify-between">
              <h3 className="font-semibold text-surface-800">Line Items</h3>
              <button onClick={() => setShowTemplates(true)} className="btn-secondary text-xs py-1.5 px-3">Use Template</button>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {quotation.items.map((item, index) => (
                  <div
                    key={item.id || index}
                    draggable
                    onDragStart={() => handleDragStart(index)}
                    onDragOver={(e) => handleDragOver(e, index)}
                    onDragEnd={handleDragEnd}
                    className={`relative p-5 bg-surface-50 rounded-lg border transition-all ${dragIndex === index ? 'border-primary-400 shadow-md opacity-75' : 'border-surface-200'}`}
                  >
                    <div className="absolute top-4 left-3 cursor-grab text-surface-300 hover:text-surface-500">
                      <FiMove className="text-sm" />
                    </div>
                    {quotation.items.length > 1 && (
                      <button onClick={() => removeItem(index)} className="absolute top-4 right-4 text-surface-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded transition-colors" title="Remove">
                        <FiTrash2 className="text-lg" />
                      </button>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-12 gap-4 pl-5">
                      <div className="md:col-span-6 space-y-3">
                        <div className="relative">
                          <label className="form-label">Item / Service Name</label>
                          <input
                            type="text" value={item.name}
                            onChange={(e) => { updateItem(index, 'name', e.target.value); setActiveSuggestion(e.target.value.length > 1 ? index : null); }}
                            onBlur={() => setTimeout(() => setActiveSuggestion(null), 200)}
                            placeholder="e.g. CCTV Camera" className="input-field"
                          />
                          {activeSuggestion === index && suggestions.length > 0 && (
                            <div className="absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-surface-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                              {suggestions.filter(s => s.name.toLowerCase().includes((item.name || '').toLowerCase())).slice(0, 5).map((s, si) => (
                                <button key={si} onMouseDown={() => applySuggestion(index, s)} className="w-full text-left px-3 py-2 text-sm hover:bg-primary-50 border-b border-surface-100 last:border-0">
                                  <span className="font-medium text-surface-800">{s.name}</span>
                                  <span className="text-surface-400 ml-2 text-xs">{formatCurrency(s.price)}</span>
                                </button>
                              ))}
                            </div>
                          )}
                        </div>
                        <div>
                          <label className="form-label text-surface-400">Description (Optional)</label>
                          <textarea value={item.description} onChange={(e) => updateItem(index, 'description', e.target.value)} placeholder="Detailed specs or notes" rows={2} className="input-field resize-none" />
                        </div>
                      </div>

                      <div className="md:col-span-6">
                        <div className="grid grid-cols-3 gap-3">
                          <div>
                            <label className="form-label">Category</label>
                            <select value={item.category} onChange={(e) => updateItem(index, 'category', e.target.value)} className="select-field text-xs">
                              {CATEGORIES.map(cat => <option key={cat.id} value={cat.id}>{cat.label}</option>)}
                            </select>
                          </div>
                          <div>
                            <label className="form-label">HSN/SAC</label>
                            <input type="text" value={item.hsnCode || ''} onChange={(e) => updateItem(index, 'hsnCode', e.target.value)} placeholder="e.g. 8528" className="input-field font-mono text-xs" maxLength={8} />
                          </div>
                          <div>
                            <label className="form-label">Unit</label>
                            <select value={item.unit || 'nos'} onChange={(e) => updateItem(index, 'unit', e.target.value)} className="select-field text-xs">
                              {UNITS.map(u => <option key={u.id} value={u.id}>{u.label}</option>)}
                            </select>
                          </div>
                          <div>
                            <label className="form-label">Qty</label>
                            <input type="number" min="1" value={item.quantity} onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value) || 1)} className="input-field" />
                          </div>
                          <div className="col-span-2">
                            <label className="form-label">Unit Price (₹)</label>
                            <input type="number" min="0" value={item.price} onChange={(e) => updateItem(index, 'price', parseFloat(e.target.value) || 0)} className="input-field" />
                          </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-surface-200 text-right">
                          <span className="text-surface-500 text-sm mr-2">Line Total:</span>
                          <span className="font-semibold text-surface-800 text-lg">{formatCurrency((item.quantity || 0) * (item.price || 0))}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <button onClick={addItem} className="mt-4 w-full py-3 rounded-lg border-2 border-dashed border-surface-300 text-surface-600 font-medium hover:border-primary-400 hover:text-primary-600 hover:bg-primary-50 transition-all flex items-center justify-center gap-2">
                <FiPlus className="text-lg" /> Add Another Item
              </button>
            </div>
          </div>

          {/* Notes & Terms */}
          <div className="bg-white rounded-xl shadow-sm border border-surface-200 overflow-hidden">
            <div className="bg-surface-50 px-6 py-4 border-b border-surface-200 flex items-center justify-between">
              <h3 className="font-semibold text-surface-800">Notes & Terms</h3>
              <div className="flex gap-1">
                {TERMS_TEMPLATES.map(t => (
                  <button key={t.id} onClick={() => applyTermsTemplate(t.id)} className="text-[10px] px-2 py-1 rounded bg-surface-100 text-surface-600 hover:bg-primary-50 hover:text-primary-700 transition-all font-medium">
                    {t.label}
                  </button>
                ))}
              </div>
            </div>
            <div className="p-6 space-y-5">
              <div>
                <label className="form-label">Additional Notes</label>
                <textarea value={quotation.notes} onChange={(e) => updateField('notes', e.target.value)} placeholder="Any additional notes for the client..." rows={2} className="input-field resize-none" />
              </div>
              <div>
                <label className="form-label">Terms & Conditions</label>
                <textarea value={quotation.termsAndConditions} onChange={(e) => updateField('termsAndConditions', e.target.value)} rows={4} className="input-field resize-none" />
              </div>
            </div>
          </div>
        </div>

        {/* Right Column — Summary */}
        <div>
          <div className="bg-white rounded-xl shadow-sm border border-surface-200 p-6 sticky top-6">
            <h3 className="font-bold text-lg text-surface-800 mb-6 border-b border-surface-100 pb-4">Financial Summary</h3>

            <div className="space-y-5">
              {/* GST Toggle */}
              <div className="flex items-center justify-between p-4 bg-surface-50 rounded-lg border border-surface-200">
                <div>
                  <p className="font-medium text-surface-800 text-sm">Apply Tax (GST)</p>
                  <p className="text-xs text-surface-500 mt-0.5">Calculate GST on subtotal</p>
                </div>
                <div className={`toggle-switch ${quotation.gstEnabled ? 'active' : ''}`} onClick={() => updateField('gstEnabled', !quotation.gstEnabled)}>
                  <div className="toggle-knob" />
                </div>
              </div>

              {quotation.gstEnabled && (
                <div className="space-y-3">
                  <div>
                    <label className="form-label">GST Rate</label>
                    <select value={quotation.gstRate} onChange={(e) => updateField('gstRate', parseFloat(e.target.value))} className="select-field">
                      <option value={5}>5%</option>
                      <option value={12}>12%</option>
                      <option value={18}>18%</option>
                      <option value={28}>28%</option>
                    </select>
                  </div>
                  <div>
                    <label className="form-label">GST Type</label>
                    <select value={quotation.gstType || 'cgst_sgst'} onChange={(e) => updateField('gstType', e.target.value)} className="select-field">
                      {GST_TYPES.map(t => <option key={t.id} value={t.id}>{t.label}</option>)}
                    </select>
                  </div>
                </div>
              )}

              {/* Discount */}
              <div>
                <label className="form-label">Discount</label>
                <div className="flex">
                  <div className="relative flex-1">
                    <input type="number" min="0" value={quotation.discount} onChange={(e) => updateField('discount', parseFloat(e.target.value) || 0)} className="input-field rounded-r-none border-r-0 h-full" placeholder="0" />
                  </div>
                  <div className="flex">
                    <button onClick={() => updateField('discountType', 'amount')} className={`px-3 py-2 text-sm border font-medium ${quotation.discountType === 'amount' ? 'bg-primary-50 border-primary-500 text-primary-700 z-10' : 'bg-surface-50 border-surface-300 text-surface-500 hover:bg-surface-100'}`}>₹</button>
                    <button onClick={() => updateField('discountType', 'percent')} className={`px-3 py-2 text-sm border font-medium rounded-r-md ${quotation.discountType === 'percent' ? 'bg-primary-50 border-primary-500 text-primary-700 z-10 ml-[-1px]' : 'bg-surface-50 border-surface-300 text-surface-500 hover:bg-surface-100 ml-[-1px]'}`}>%</button>
                  </div>
                </div>
              </div>

              {/* Calculations */}
              <div className="bg-surface-50 p-4 rounded-lg border border-surface-200 space-y-3">
                <div className="flex justify-between text-sm text-surface-600">
                  <span>Subtotal</span>
                  <span className="font-medium text-surface-800">{formatCurrency(calculations.subtotal)}</span>
                </div>
                {calculations.discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-red-600">
                    <span>Discount {quotation.discountType === 'percent' ? `(${quotation.discount}%)` : ''}</span>
                    <span className="font-medium">- {formatCurrency(calculations.discountAmount)}</span>
                  </div>
                )}
                {quotation.gstEnabled && (
                  calculations.isIntraState ? (
                    <>
                      <div className="flex justify-between text-sm text-surface-600">
                        <span>CGST ({quotation.gstRate / 2}%)</span>
                        <span className="font-medium text-surface-800">{formatCurrency(calculations.cgst)}</span>
                      </div>
                      <div className="flex justify-between text-sm text-surface-600">
                        <span>SGST ({quotation.gstRate / 2}%)</span>
                        <span className="font-medium text-surface-800">{formatCurrency(calculations.sgst)}</span>
                      </div>
                    </>
                  ) : (
                    <div className="flex justify-between text-sm text-surface-600">
                      <span>IGST ({quotation.gstRate}%)</span>
                      <span className="font-medium text-surface-800">{formatCurrency(calculations.igst)}</span>
                    </div>
                  )
                )}
                <div className="pt-3 mt-1 border-t border-surface-200 flex justify-between items-center">
                  <span className="font-bold text-surface-900">Grand Total</span>
                  <span className="text-xl font-bold text-primary-700">{formatCurrency(calculations.grandTotal)}</span>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="mt-8 space-y-3">
              <button onClick={handleDownloadPDF} className="btn-success w-full text-base py-3 shadow-sm">
                <FiDownload /> Download PDF
              </button>
              <button onClick={handleWhatsApp} className="btn-whatsapp w-full text-base py-3 shadow-sm">
                <FaWhatsapp /> Share via WhatsApp
              </button>
            </div>
          </div>
        </div>
      </div>

      {showTemplates && (
        <TemplatePicker onSelect={applyTemplate} onClose={() => setShowTemplates(false)} />
      )}
    </div>
  );
}

export default QuotationForm;
