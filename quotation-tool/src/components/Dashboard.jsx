import { useState, useMemo } from 'react';
import {
  FiPlus, FiEdit2, FiTrash2, FiCopy, FiSearch,
  FiFilter, FiCalendar, FiFileText, FiArrowUp,
  FiArrowDown, FiChevronDown, FiTrendingUp
} from 'react-icons/fi';
import { formatCurrency, formatDate, SORT_OPTIONS } from '../data';

function Dashboard({ quotations, onNew, onEdit, onDelete, onDuplicate }) {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [sortBy, setSortBy] = useState('newest');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [showFilters, setShowFilters] = useState(false);

  // Calculate totals for each item
  const getQuotationTotal = (q) => {
    const subtotal = (q.items || []).reduce((s, item) => s + (item.quantity || 0) * (item.price || 0), 0);
    let discountAmount = 0;
    if (q.discountType === 'percent') {
      discountAmount = (subtotal * (q.discount || 0)) / 100;
    } else {
      discountAmount = q.discount || 0;
    }
    const afterDiscount = subtotal - discountAmount;
    const gstAmount = q.gstEnabled ? (afterDiscount * (q.gstRate || 0)) / 100 : 0;
    return afterDiscount + gstAmount;
  };

  // Filter & sort quotations
  const filtered = useMemo(() => {
    let result = quotations.filter(q => {
      const matchesSearch = !search ||
        q.quotationNumber?.toLowerCase().includes(search.toLowerCase()) ||
        q.client?.name?.toLowerCase().includes(search.toLowerCase()) ||
        q.client?.company?.toLowerCase().includes(search.toLowerCase());

      const matchesStatus = statusFilter === 'all' || q.status === statusFilter;

      // Date filter
      let matchesDate = true;
      if (dateFrom) {
        matchesDate = matchesDate && q.date >= dateFrom;
      }
      if (dateTo) {
        matchesDate = matchesDate && q.date <= dateTo;
      }

      return matchesSearch && matchesStatus && matchesDate;
    });

    // Sort
    result.sort((a, b) => {
      switch (sortBy) {
        case 'newest':
          return new Date(b.createdAt) - new Date(a.createdAt);
        case 'oldest':
          return new Date(a.createdAt) - new Date(b.createdAt);
        case 'value-high':
          return getQuotationTotal(b) - getQuotationTotal(a);
        case 'value-low':
          return getQuotationTotal(a) - getQuotationTotal(b);
        case 'name-az':
          return (a.client?.name || '').localeCompare(b.client?.name || '');
        case 'name-za':
          return (b.client?.name || '').localeCompare(a.client?.name || '');
        default:
          return 0;
      }
    });

    return result;
  }, [quotations, search, statusFilter, sortBy, dateFrom, dateTo]);

  // Calculate stats
  const stats = useMemo(() => {
    const total = quotations.length;
    const totalValue = quotations.reduce((sum, q) => sum + getQuotationTotal(q), 0);
    const now = new Date();
    const thisMonth = quotations.filter(q => {
      const d = new Date(q.createdAt);
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }).length;
    const acceptedCount = quotations.filter(q => q.status === 'accepted').length;
    const conversionRate = total > 0 ? Math.round((acceptedCount / total) * 100) : 0;

    return { total, totalValue, thisMonth, acceptedCount, conversionRate };
  }, [quotations]);

  const statusColors = {
    draft: 'bg-amber-100 text-amber-700',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
  };

  const clearFilters = () => {
    setSearch('');
    setStatusFilter('all');
    setSortBy('newest');
    setDateFrom('');
    setDateTo('');
  };

  const hasActiveFilters = search || statusFilter !== 'all' || sortBy !== 'newest' || dateFrom || dateTo;

  return (
    <div className="p-4 lg:p-8 max-w-7xl mx-auto animate-in">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h2 className="text-2xl lg:text-3xl font-bold text-surface-900">
            Dashboard
          </h2>
          <p className="text-surface-500 text-sm mt-1">Manage and track your quotations</p>
        </div>
        <button onClick={onNew} className="btn-primary text-base py-3 px-6 shadow-md">
          <FiPlus className="text-lg" /> Create Quotation
        </button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div className="bg-white rounded-xl p-5 border border-surface-200 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-surface-500 text-xs uppercase tracking-wider font-semibold">Total</p>
              <p className="text-2xl font-bold text-surface-900 mt-1">{stats.total}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center">
              <FiFileText className="text-primary-600 text-lg" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-5 border border-surface-200 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-surface-500 text-xs uppercase tracking-wider font-semibold">This Month</p>
              <p className="text-2xl font-bold text-surface-900 mt-1">{stats.thisMonth}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
              <FiCalendar className="text-green-600 text-lg" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-5 border border-surface-200 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-surface-500 text-xs uppercase tracking-wider font-semibold">Total Value</p>
              <p className="text-lg font-bold text-surface-900 mt-1">{formatCurrency(stats.totalValue)}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
              <span className="text-amber-600 text-lg font-bold">₹</span>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-5 border border-surface-200 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-surface-500 text-xs uppercase tracking-wider font-semibold">Win Rate</p>
              <p className="text-2xl font-bold text-surface-900 mt-1">{stats.conversionRate}%</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
              <FiTrendingUp className="text-purple-600 text-lg" />
            </div>
          </div>
        </div>
      </div>

      {/* Search & Filters */}
      <div className="bg-white rounded-xl border border-surface-200 shadow-sm mb-6">
        <div className="p-4 flex flex-col sm:flex-row gap-3">
          <div className="flex-1 relative">
            <FiSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-surface-400 text-lg" />
            <input
              type="text"
              placeholder="Search by number, client, or company..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="input-field pl-11 bg-surface-50 border-surface-200"
            />
          </div>
          <div className="flex items-center gap-2">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="select-field w-auto bg-surface-50 border-surface-200 text-sm"
            >
              <option value="all">All Status</option>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="accepted">Accepted</option>
              <option value="rejected">Rejected</option>
            </select>

            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="select-field w-auto bg-surface-50 border-surface-200 text-sm"
            >
              {SORT_OPTIONS.map(opt => (
                <option key={opt.id} value={opt.id}>{opt.label}</option>
              ))}
            </select>

            <button
              onClick={() => setShowFilters(!showFilters)}
              className={`p-2.5 rounded-lg border text-sm transition-all ${
                showFilters || dateFrom || dateTo
                  ? 'bg-primary-50 border-primary-300 text-primary-600'
                  : 'bg-surface-50 border-surface-200 text-surface-500 hover:bg-surface-100'
              }`}
              title="Date filters"
            >
              <FiCalendar />
            </button>

            {hasActiveFilters && (
              <button
                onClick={clearFilters}
                className="text-xs text-primary-600 hover:text-primary-700 font-medium whitespace-nowrap"
              >
                Clear all
              </button>
            )}
          </div>
        </div>

        {/* Date range filter */}
        {showFilters && (
          <div className="px-4 pb-4 pt-0 flex flex-wrap gap-3 items-center border-t border-surface-100 mt-0 pt-3">
            <span className="text-xs text-surface-500 font-medium">Date range:</span>
            <div className="flex items-center gap-2">
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className="input-field text-sm py-1.5 w-auto"
                placeholder="From"
              />
              <span className="text-surface-400 text-sm">to</span>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className="input-field text-sm py-1.5 w-auto"
                placeholder="To"
              />
            </div>
          </div>
        )}
      </div>

      {/* Results count */}
      {hasActiveFilters && (
        <p className="text-xs text-surface-500 mb-3">
          Showing {filtered.length} of {quotations.length} quotation{quotations.length !== 1 ? 's' : ''}
        </p>
      )}

      {/* Quotation List */}
      {filtered.length === 0 ? (
        <div className="bg-white rounded-xl p-12 text-center border border-surface-200 shadow-sm">
          <div className="w-20 h-20 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-5">
            <FiFileText className="text-primary-500 text-3xl" />
          </div>
          <h3 className="text-xl font-bold text-surface-900 mb-2">
            {quotations.length === 0 ? 'No quotations yet' : 'No results found'}
          </h3>
          <p className="text-surface-500 text-base mb-8 max-w-md mx-auto">
            {quotations.length === 0
              ? 'Create your first quotation to start generating beautiful PDFs for your clients.'
              : 'Try adjusting your search or filter criteria to find what you are looking for.'}
          </p>
          {quotations.length === 0 && (
            <button onClick={onNew} className="btn-primary mx-auto py-3 px-6 shadow-md">
              <FiPlus /> Create First Quotation
            </button>
          )}
        </div>
      ) : (
        <div className="space-y-3">
          {filtered.map((q) => {
            const total = getQuotationTotal(q);

            return (
              <div
                key={q.id}
                className="bg-white p-5 rounded-xl border border-surface-200 hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group"
                onClick={() => onEdit(q)}
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-3 mb-1.5">
                      <span className="text-primary-700 font-mono font-bold text-sm">
                        {q.quotationNumber}
                      </span>
                      <span className={`text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full ${statusColors[q.status] || statusColors.draft}`}>
                        {q.status}
                      </span>
                    </div>
                    <p className="text-surface-900 font-semibold truncate text-lg">
                      {q.client?.name || 'No client name'}
                      {q.client?.company && (
                        <span className="text-surface-500 font-normal"> — {q.client.company}</span>
                      )}
                    </p>
                    <p className="text-surface-500 text-sm mt-1">
                      {formatDate(q.date)} • {(q.items || []).length} item{(q.items || []).length !== 1 ? 's' : ''}
                      {q.gstEnabled && <span className="text-surface-400"> • GST {q.gstRate}%</span>}
                    </p>
                  </div>

                  <div className="flex items-center gap-4">
                    <span className="text-xl font-bold text-surface-800">
                      {formatCurrency(total)}
                    </span>
                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button
                        onClick={(e) => { e.stopPropagation(); onDuplicate(q); }}
                        className="p-2 rounded-lg hover:bg-surface-100 text-surface-400 hover:text-primary-600 transition-all"
                        title="Duplicate"
                      >
                        <FiCopy className="text-lg" />
                      </button>
                      <button
                        onClick={(e) => { e.stopPropagation(); onDelete(q.id); }}
                        className="p-2 rounded-lg hover:bg-red-50 text-surface-400 hover:text-red-600 transition-all"
                        title="Delete"
                      >
                        <FiTrash2 className="text-lg" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

export default Dashboard;
