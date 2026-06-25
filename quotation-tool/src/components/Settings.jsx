import { useState, useEffect } from 'react';
import {
  FiSave, FiImage, FiTrash2, FiBriefcase, FiPhone, FiMail,
  FiGlobe, FiMapPin, FiHash, FiCreditCard, FiEdit3
} from 'react-icons/fi';
import { COMPANY_INFO } from '../data';
import { saveSettings, loadSettings } from '../storage';

function Settings({ settings: initialSettings, onSave }) {
  const [formData, setFormData] = useState({
    companyName: COMPANY_INFO.name,
    tagline: COMPANY_INFO.tagline,
    address: COMPANY_INFO.address,
    phone: COMPANY_INFO.phone,
    email: COMPANY_INFO.email,
    website: COMPANY_INFO.website,
    gstin: COMPANY_INFO.gstin,
    footerNote: COMPANY_INFO.footerNote,
    logoDataUrl: null,
    // Bank details
    bankName: COMPANY_INFO.bankName || '',
    bankAccountNo: COMPANY_INFO.bankAccountNo || '',
    bankIfsc: COMPANY_INFO.bankIfsc || '',
    bankBranch: COMPANY_INFO.bankBranch || '',
    // Signature
    signatureDataUrl: null,
  });

  const [activeTab, setActiveTab] = useState('company');

  useEffect(() => {
    if (initialSettings) {
      setFormData(prev => ({ ...prev, ...initialSettings }));
    }
  }, [initialSettings]);

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 500 * 1024) {
      alert('Logo file is too large. Please use an image under 500KB.');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      handleChange('logoDataUrl', event.target.result);
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveLogo = () => {
    handleChange('logoDataUrl', null);
  };

  const handleSignatureUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 300 * 1024) {
      alert('Signature file is too large. Please use an image under 300KB.');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      handleChange('signatureDataUrl', event.target.result);
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveSignature = () => {
    handleChange('signatureDataUrl', null);
  };

  const handleSave = () => {
    saveSettings(formData);
    onSave(formData);
  };

  const tabs = [
    { id: 'company', label: 'Company', icon: <FiBriefcase /> },
    { id: 'banking', label: 'Bank Details', icon: <FiCreditCard /> },
    { id: 'branding', label: 'Branding', icon: <FiImage /> },
  ];

  return (
    <div className="p-4 sm:p-6 lg:p-8 max-w-3xl mx-auto animate-in">
      {/* Header */}
      <div className="mb-8 border-b border-surface-200 pb-6">
        <h2 className="text-2xl font-bold text-surface-900 flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600">
            <FiBriefcase className="text-xl" />
          </div>
          Company Settings
        </h2>
        <p className="text-surface-500 mt-2 text-sm">
          Configure your company details for quotation headers and PDFs.
        </p>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 mb-6 bg-surface-100 p-1 rounded-xl">
        {tabs.map(tab => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium transition-all ${
              activeTab === tab.id
                ? 'bg-white text-primary-700 shadow-sm border border-surface-200'
                : 'text-surface-500 hover:text-surface-700'
            }`}
          >
            {tab.icon}
            {tab.label}
          </button>
        ))}
      </div>

      {/* Company Tab */}
      {activeTab === 'company' && (
        <div className="space-y-6">
          <div className="bg-white border border-surface-200 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-surface-800 mb-4 flex items-center gap-2 border-b border-surface-100 pb-3">
              <FiBriefcase className="text-primary-500" />
              Company Details
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
              <div className="sm:col-span-2">
                <label className="form-label">
                  <FiBriefcase className="inline mr-1 text-surface-400" />
                  Company Name
                </label>
                <input
                  type="text"
                  value={formData.companyName}
                  onChange={(e) => handleChange('companyName', e.target.value)}
                  className="input-field"
                  placeholder="Your Company Name"
                />
              </div>

              <div className="sm:col-span-2">
                <label className="form-label">Tagline</label>
                <input
                  type="text"
                  value={formData.tagline}
                  onChange={(e) => handleChange('tagline', e.target.value)}
                  className="input-field"
                  placeholder="Your Company Tagline"
                />
              </div>

              <div>
                <label className="form-label">
                  <FiPhone className="inline mr-1 text-surface-400" />
                  Phone
                </label>
                <input
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  className="input-field"
                  placeholder="+91 XXXXX XXXXX"
                />
              </div>

              <div>
                <label className="form-label">
                  <FiMail className="inline mr-1 text-surface-400" />
                  Email
                </label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  className="input-field"
                  placeholder="info@company.com"
                />
              </div>

              <div>
                <label className="form-label">
                  <FiGlobe className="inline mr-1 text-surface-400" />
                  Website
                </label>
                <input
                  type="text"
                  value={formData.website}
                  onChange={(e) => handleChange('website', e.target.value)}
                  className="input-field"
                  placeholder="www.company.com"
                />
              </div>

              <div>
                <label className="form-label">
                  <FiHash className="inline mr-1 text-surface-400" />
                  GSTIN
                </label>
                <input
                  type="text"
                  value={formData.gstin}
                  onChange={(e) => handleChange('gstin', e.target.value.toUpperCase())}
                  className="input-field"
                  placeholder="22AAAAA0000A1Z5"
                  maxLength={15}
                />
              </div>

              <div className="sm:col-span-2">
                <label className="form-label">
                  <FiMapPin className="inline mr-1 text-surface-400" />
                  Address
                </label>
                <textarea
                  value={formData.address}
                  onChange={(e) => handleChange('address', e.target.value)}
                  className="input-field resize-none"
                  rows={3}
                  placeholder="Full company address"
                />
              </div>

              <div className="sm:col-span-2">
                <label className="form-label">PDF Footer Note</label>
                <textarea
                  value={formData.footerNote}
                  onChange={(e) => handleChange('footerNote', e.target.value)}
                  className="input-field resize-none"
                  rows={2}
                  placeholder="Thank you note for PDF footer"
                />
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Banking Tab */}
      {activeTab === 'banking' && (
        <div className="space-y-6">
          <div className="bg-white border border-surface-200 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-surface-800 mb-4 flex items-center gap-2 border-b border-surface-100 pb-3">
              <FiCreditCard className="text-primary-500" />
              Bank Account Details
              <span className="ml-auto text-xs text-surface-400 font-normal">Shown on PDF for payments</span>
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
              <div className="sm:col-span-2">
                <label className="form-label">Bank Name</label>
                <input
                  type="text"
                  value={formData.bankName}
                  onChange={(e) => handleChange('bankName', e.target.value)}
                  className="input-field"
                  placeholder="e.g. State Bank of India"
                />
              </div>

              <div>
                <label className="form-label">Account Number</label>
                <input
                  type="text"
                  value={formData.bankAccountNo}
                  onChange={(e) => handleChange('bankAccountNo', e.target.value)}
                  className="input-field"
                  placeholder="XXXXXXXXXXXX"
                />
              </div>

              <div>
                <label className="form-label">IFSC Code</label>
                <input
                  type="text"
                  value={formData.bankIfsc}
                  onChange={(e) => handleChange('bankIfsc', e.target.value.toUpperCase())}
                  className="input-field"
                  placeholder="SBIN0001234"
                  maxLength={11}
                />
              </div>

              <div className="sm:col-span-2">
                <label className="form-label">Branch</label>
                <input
                  type="text"
                  value={formData.bankBranch}
                  onChange={(e) => handleChange('bankBranch', e.target.value)}
                  className="input-field"
                  placeholder="e.g. Ahmedabad Main Branch"
                />
              </div>
            </div>

            {/* Bank details preview */}
            {formData.bankName && (
              <div className="mt-5 pt-4 border-t border-surface-100">
                <p className="text-xs font-semibold text-surface-500 uppercase tracking-wider mb-3">Preview on PDF</p>
                <div className="bg-surface-50 rounded-lg p-4 border border-surface-200 text-sm">
                  <p className="font-semibold text-surface-800 mb-1">Bank Details for Payment</p>
                  <div className="grid grid-cols-2 gap-1 text-surface-600 text-xs">
                    <span className="text-surface-500">Bank:</span>
                    <span>{formData.bankName}</span>
                    {formData.bankAccountNo && <>
                      <span className="text-surface-500">A/C No:</span>
                      <span className="font-mono">{formData.bankAccountNo}</span>
                    </>}
                    {formData.bankIfsc && <>
                      <span className="text-surface-500">IFSC:</span>
                      <span className="font-mono">{formData.bankIfsc}</span>
                    </>}
                    {formData.bankBranch && <>
                      <span className="text-surface-500">Branch:</span>
                      <span>{formData.bankBranch}</span>
                    </>}
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Branding Tab */}
      {activeTab === 'branding' && (
        <div className="space-y-6">
          {/* Logo Section */}
          <div className="bg-white border border-surface-200 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-surface-800 mb-4 flex items-center gap-2">
              <FiImage className="text-primary-500" />
              Company Logo
            </h3>

            <div className="flex items-center gap-6">
              {formData.logoDataUrl ? (
                <div className="relative group">
                  <div className="w-24 h-24 rounded-xl border border-surface-200 overflow-hidden bg-surface-50 flex items-center justify-center">
                    <img
                      src={formData.logoDataUrl}
                      alt="Company Logo"
                      className="max-w-full max-h-full object-contain"
                    />
                  </div>
                  <button
                    onClick={handleRemoveLogo}
                    className="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <FiTrash2 className="text-[10px]" />
                  </button>
                </div>
              ) : (
                <div className="w-24 h-24 rounded-xl border-2 border-dashed border-surface-300 flex items-center justify-center text-surface-400 bg-surface-50">
                  <FiImage className="text-2xl" />
                </div>
              )}

              <div className="flex-1">
                <label className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-100 text-surface-700 text-sm font-medium cursor-pointer hover:bg-surface-200 transition-all border border-surface-200">
                  <FiImage />
                  {formData.logoDataUrl ? 'Change Logo' : 'Upload Logo'}
                  <input
                    type="file"
                    accept="image/*"
                    onChange={handleLogoUpload}
                    className="hidden"
                  />
                </label>
                <p className="text-xs text-surface-500 mt-2">
                  PNG, JPG or SVG. Max 500KB. Recommended: 200×80px
                </p>
              </div>
            </div>
          </div>

          {/* Signature Section */}
          <div className="bg-white border border-surface-200 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-surface-800 mb-4 flex items-center gap-2">
              <FiEdit3 className="text-primary-500" />
              Authorized Signature
              <span className="ml-auto text-xs text-surface-400 font-normal">Appears at bottom of PDF</span>
            </h3>

            <div className="flex items-center gap-6">
              {formData.signatureDataUrl ? (
                <div className="relative group">
                  <div className="w-40 h-20 rounded-lg border border-surface-200 overflow-hidden bg-white flex items-center justify-center p-2">
                    <img
                      src={formData.signatureDataUrl}
                      alt="Signature"
                      className="max-w-full max-h-full object-contain"
                    />
                  </div>
                  <button
                    onClick={handleRemoveSignature}
                    className="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <FiTrash2 className="text-[10px]" />
                  </button>
                </div>
              ) : (
                <div className="w-40 h-20 rounded-lg border-2 border-dashed border-surface-300 flex flex-col items-center justify-center text-surface-400 bg-surface-50">
                  <FiEdit3 className="text-xl mb-1" />
                  <span className="text-[10px]">No signature</span>
                </div>
              )}

              <div className="flex-1">
                <label className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-100 text-surface-700 text-sm font-medium cursor-pointer hover:bg-surface-200 transition-all border border-surface-200">
                  <FiEdit3 />
                  {formData.signatureDataUrl ? 'Change Signature' : 'Upload Signature'}
                  <input
                    type="file"
                    accept="image/*"
                    onChange={handleSignatureUpload}
                    className="hidden"
                  />
                </label>
                <p className="text-xs text-surface-500 mt-2">
                  PNG with transparent background. Max 300KB. Recommended: 300×100px
                </p>
              </div>
            </div>
          </div>

          {/* Preview Card */}
          <div className="bg-surface-50 border border-surface-200 rounded-xl p-6">
            <h3 className="text-sm font-semibold text-surface-600 mb-4 uppercase tracking-wider">Preview (PDF Header)</h3>
            <div className="bg-white rounded border border-surface-200 p-5 shadow-sm">
              <div className="flex items-center gap-4">
                {formData.logoDataUrl && (
                  <img src={formData.logoDataUrl} alt="Logo" className="h-12 object-contain" />
                )}
                <div>
                  <h4 className="text-lg font-bold text-surface-900">{formData.companyName || 'Company Name'}</h4>
                  <p className="text-xs text-surface-500">{formData.tagline || 'Tagline'}</p>
                </div>
              </div>
              <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-surface-600">
                {formData.phone && <span>📞 {formData.phone}</span>}
                {formData.email && <span>✉️ {formData.email}</span>}
                {formData.website && <span>🌐 {formData.website}</span>}
              </div>
              {formData.address && (
                <p className="mt-1 text-[11px] text-surface-500">📍 {formData.address}</p>
              )}
              {formData.gstin && (
                <p className="mt-1 text-[10px] text-surface-400 font-mono">GSTIN: {formData.gstin}</p>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Save Button */}
      <button
        onClick={handleSave}
        className="btn-primary w-full text-base py-3 shadow-sm mt-6"
      >
        <FiSave className="text-lg" />
        Save Settings
      </button>
    </div>
  );
}

export default Settings;
