import { FiX, FiCheck } from 'react-icons/fi';
import { TEMPLATES, formatCurrency } from '../data';

function TemplatePicker({ onSelect, onClose }) {
  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      {/* Overlay */}
      <div className="absolute inset-0 bg-surface-900/40 backdrop-blur-sm" onClick={onClose} />

      {/* Modal */}
      <div className="relative bg-white border border-surface-200 rounded-xl shadow-xl w-full max-w-4xl max-h-[85vh] flex flex-col animate-in">
        {/* Header */}
        <div className="bg-white p-5 border-b border-surface-200 flex items-center justify-between rounded-t-xl shrink-0">
          <div>
            <h3 className="text-lg font-bold text-surface-900">📋 Quick Templates</h3>
            <p className="text-xs text-surface-500 mt-1">Select a template to auto-fill items and pricing</p>
          </div>
          <button onClick={onClose} className="p-2 rounded-lg hover:bg-surface-100 text-surface-400 hover:text-surface-700 transition-all">
            <FiX className="text-xl" />
          </button>
        </div>

        {/* Template List */}
        <div className="p-5 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-5">
          {TEMPLATES.map(template => {
            const totalPrice = template.items.reduce(
              (sum, item) => sum + item.quantity * item.price, 0
            );

            return (
              <div
                key={template.id}
                className="bg-surface-50 p-5 rounded-xl border border-surface-200 hover:border-primary-400 hover:shadow-md transition-all duration-300 cursor-pointer group flex flex-col"
                onClick={() => onSelect(template)}
              >
                <div className="flex items-start justify-between mb-4 border-b border-surface-200 pb-4">
                  <div>
                    <h4 className="font-bold text-surface-900 text-base">{template.name}</h4>
                    <p className="text-xs text-surface-500 mt-1.5">{template.description}</p>
                  </div>
                  <div className="p-2 rounded-lg bg-primary-50 text-primary-600 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 ml-4">
                    <FiCheck className="text-lg" />
                  </div>
                </div>

                <div className="space-y-2.5 mb-5 flex-1">
                  {template.items.map((item, i) => (
                    <div key={i} className="flex items-center justify-between text-sm">
                      <span className="text-surface-700 truncate flex-1 mr-3 font-medium">
                        {item.name}
                      </span>
                      <span className="text-surface-900 font-semibold whitespace-nowrap">
                        {item.quantity} × {formatCurrency(item.price)}
                      </span>
                    </div>
                  ))}
                </div>

                <div className="pt-4 border-t border-surface-200 flex items-center justify-between mt-auto">
                  <span className="text-xs font-semibold text-surface-600 bg-white px-2.5 py-1 rounded-md border border-surface-200">
                    {template.items.length} items
                  </span>
                  <span className="text-base font-bold text-primary-700">
                    {formatCurrency(totalPrice)}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}

export default TemplatePicker;
