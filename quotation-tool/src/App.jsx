import { useState, useEffect, useCallback } from 'react';
import { FiPlus, FiFileText, FiSettings, FiMenu, FiX, FiDownload, FiUpload } from 'react-icons/fi';
import QuotationForm from './components/QuotationForm';
import Dashboard from './components/Dashboard';
import Settings from './components/Settings';
import { loadQuotations, saveQuotation, deleteQuotation, loadSettings, exportQuotations, importQuotations } from './storage';
import { createEmptyQuotation } from './data';
import toast from 'react-hot-toast';

function App() {
  const [view, setView] = useState('dashboard'); // 'dashboard' | 'editor' | 'settings'
  const [quotations, setQuotations] = useState([]);
  const [currentQuotation, setCurrentQuotation] = useState(null);
  const [settings, setSettings] = useState(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  // Load data on mount
  useEffect(() => {
    setQuotations(loadQuotations());
    const s = loadSettings();
    if (s) {
      setSettings(s);
    } else {
      // First launch — prompt to fill company details
      setView('settings');
    }
  }, []);

  // Create new quotation
  const handleNew = useCallback(() => {
    const newQ = createEmptyQuotation();
    setCurrentQuotation(newQ);
    setView('editor');
    setSidebarOpen(false);
  }, []);

  // Edit existing quotation
  const handleEdit = useCallback((quotation) => {
    setCurrentQuotation({ ...quotation });
    setView('editor');
    setSidebarOpen(false);
  }, []);

  // Save quotation (silent = true for auto-save)
  const handleSave = useCallback((quotation, silent = false) => {
    const saved = saveQuotation(quotation);
    setQuotations(loadQuotations());
    setCurrentQuotation(saved);
    if (!silent) toast.success('Quotation saved successfully!');
  }, []);

  // Delete quotation
  const handleDelete = useCallback((id) => {
    if (window.confirm('Are you sure you want to delete this quotation?')) {
      const remaining = deleteQuotation(id);
      setQuotations(remaining);
      if (currentQuotation?.id === id) {
        setCurrentQuotation(null);
        setView('dashboard');
      }
      toast.success('Quotation deleted');
    }
  }, [currentQuotation]);

  // Duplicate quotation
  const handleDuplicate = useCallback((quotation) => {
    const newQ = createEmptyQuotation();
    const duplicated = {
      ...quotation,
      id: newQ.id,
      quotationNumber: newQ.quotationNumber,
      date: newQ.date,
      status: 'draft',
      createdAt: newQ.createdAt,
      updatedAt: newQ.updatedAt,
    };
    const saved = saveQuotation(duplicated);
    setQuotations(loadQuotations());
    setCurrentQuotation(saved);
    setView('editor');
    toast.success('Quotation duplicated!');
  }, []);

  // Export
  const handleExport = useCallback(() => {
    exportQuotations();
    toast.success('Quotations exported!');
  }, []);

  // Import
  const handleImport = useCallback(async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    try {
      const updated = await importQuotations(file);
      setQuotations(updated);
      toast.success(`Imported ${updated.length} quotations`);
    } catch (err) {
      toast.error('Failed to import: ' + err.message);
    }
    e.target.value = '';
  }, []);

  return (
    <div className="min-h-screen flex flex-col lg:flex-row">
      {/* Mobile Header */}
      <div className="lg:hidden flex items-center justify-between p-4 glass-card rounded-none border-t-0 border-l-0 border-r-0">
        <div className="flex items-center gap-3">
          <button onClick={() => setSidebarOpen(!sidebarOpen)} className="text-primary-400 text-xl">
            {sidebarOpen ? <FiX /> : <FiMenu />}
          </button>
          <h1 className="text-lg font-bold text-primary-700">
            Shreeji QuotePro
          </h1>
        </div>
        <button onClick={handleNew} className="btn-primary text-sm py-2 px-3">
          <FiPlus /> New
        </button>
      </div>

      {/* Sidebar */}
      <aside className={`
        fixed lg:static inset-y-0 left-0 z-50 w-72 bg-white border-r border-surface-200
        transform transition-transform duration-300 ease-in-out
        ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0
        flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.02)]
      `}>
        {/* Logo section */}
        <div className="p-6 border-b border-surface-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
              S
            </div>
            <div>
              <h1 className="text-lg font-bold text-surface-900">
                Shreeji QuotePro
              </h1>
              <p className="text-[10px] text-surface-500 tracking-wider uppercase font-medium">Quotation Generator</p>
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 p-4 space-y-1.5">
          <button
            onClick={() => { setView('dashboard'); setSidebarOpen(false); }}
            className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
              view === 'dashboard'
                ? 'bg-primary-50 text-primary-700 border border-primary-200/50'
                : 'text-surface-600 hover:bg-surface-50 hover:text-surface-900'
            }`}
          >
            <FiFileText className={`text-lg ${view === 'dashboard' ? 'text-primary-600' : 'text-surface-400'}`} />
            Dashboard
            <span className="ml-auto text-xs bg-surface-100 text-surface-600 px-2.5 py-0.5 rounded-full font-semibold">
              {quotations.length}
            </span>
          </button>

          <button
            onClick={handleNew}
            className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
              view === 'editor'
                ? 'bg-primary-50 text-primary-700 border border-primary-200/50'
                : 'text-surface-600 hover:bg-surface-50 hover:text-surface-900'
            }`}
          >
            <FiPlus className={`text-lg ${view === 'editor' ? 'text-primary-600' : 'text-surface-400'}`} />
            New Quotation
          </button>

          <button
            onClick={() => { setView('settings'); setSidebarOpen(false); }}
            className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
              view === 'settings'
                ? 'bg-primary-50 text-primary-700 border border-primary-200/50'
                : 'text-surface-600 hover:bg-surface-50 hover:text-surface-900'
            }`}
          >
            <FiSettings className={`text-lg ${view === 'settings' ? 'text-primary-600' : 'text-surface-400'}`} />
            Settings
          </button>
        </nav>

        {/* Data management & Footer */}
        <div className="p-4 border-t border-surface-200 space-y-1 text-xs">
          <button onClick={handleExport} className="w-full flex items-center gap-2 px-3 py-2 text-surface-500 hover:text-surface-800 hover:bg-surface-50 rounded-lg transition-all">
            <FiDownload className="text-sm" /> Export Data
          </button>
          
          <label className="w-full flex items-center gap-2 px-3 py-2 text-surface-500 hover:text-surface-800 hover:bg-surface-50 rounded-lg transition-all cursor-pointer">
            <FiUpload className="text-sm" /> Import Data
            <input
              type="file"
              accept=".json"
              onChange={handleImport}
              className="hidden"
            />
          </label>

          <div className="pt-3 pb-1 text-center text-[10px] text-surface-400">
            © {new Date().getFullYear()} Shreeji Infotech
          </div>
        </div>
      </aside>

      {/* Overlay for mobile sidebar */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 bg-surface-900/50 backdrop-blur-sm z-40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Main Content */}
      <main className="flex-1 min-h-screen overflow-y-auto bg-surface-50">
        {view === 'dashboard' && (
          <Dashboard
            quotations={quotations}
            onNew={handleNew}
            onEdit={handleEdit}
            onDelete={handleDelete}
            onDuplicate={handleDuplicate}
          />
        )}
        {view === 'editor' && (
          <QuotationForm
            quotation={currentQuotation}
            onSave={handleSave}
            onBack={() => setView('dashboard')}
            settings={settings}
          />
        )}
        {view === 'settings' && (
          <Settings
            settings={settings}
            onSave={(s) => {
              setSettings(s);
              toast.success('Settings saved!');
            }}
          />
        )}
      </main>
    </div>
  );
}

export default App;
