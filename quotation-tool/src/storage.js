const STORAGE_KEY = 'shreeji_quotations';
const SETTINGS_KEY = 'shreeji_settings';
const BACKUP_KEY = 'shreeji_backup';
const MAX_BACKUPS = 5;

// Save all quotations
export function saveQuotations(quotations) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(quotations));
    return true;
  } catch (e) {
    console.error('Failed to save quotations:', e);
    return false;
  }
}

// Load all quotations
export function loadQuotations() {
  try {
    const data = localStorage.getItem(STORAGE_KEY);
    return data ? JSON.parse(data) : [];
  } catch (e) {
    console.error('Failed to load quotations:', e);
    return [];
  }
}

// Save a single quotation (add or update)
export function saveQuotation(quotation) {
  const quotations = loadQuotations();
  const index = quotations.findIndex(q => q.id === quotation.id);
  const updated = { ...quotation, updatedAt: new Date().toISOString() };

  if (index >= 0) {
    quotations[index] = updated;
  } else {
    quotations.unshift(updated);
  }

  saveQuotations(quotations);
  // Create auto-backup after each save
  createAutoBackup(quotations);
  return updated;
}

// Delete a quotation
export function deleteQuotation(id) {
  const quotations = loadQuotations();
  const filtered = quotations.filter(q => q.id !== id);
  saveQuotations(filtered);
  return filtered;
}

// Save settings (company info, logo, bank details, signature)
export function saveSettings(settings) {
  try {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
    return true;
  } catch (e) {
    console.error('Failed to save settings:', e);
    return false;
  }
}

// Load settings
export function loadSettings() {
  try {
    const data = localStorage.getItem(SETTINGS_KEY);
    return data ? JSON.parse(data) : null;
  } catch (e) {
    console.error('Failed to load settings:', e);
    return null;
  }
}

// Export quotation data as JSON (with settings)
export function exportQuotations() {
  const quotations = loadQuotations();
  const settings = loadSettings();
  const exportData = {
    version: 2,
    exportedAt: new Date().toISOString(),
    quotations,
    settings,
  };
  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `shreeji-quotations-backup-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
}

// Import quotation data from JSON
export function importQuotations(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const raw = JSON.parse(e.target.result);
        let data;
        
        // Support both v1 (array) and v2 (object with version) formats
        if (Array.isArray(raw)) {
          data = raw;
        } else if (raw.version === 2 && Array.isArray(raw.quotations)) {
          data = raw.quotations;
          // Also restore settings if present
          if (raw.settings) {
            saveSettings(raw.settings);
          }
        } else {
          reject(new Error('Invalid file format'));
          return;
        }

        const existing = loadQuotations();
        const merged = [...data, ...existing];
        // De-duplicate by id
        const unique = merged.filter((q, i, arr) => arr.findIndex(x => x.id === q.id) === i);
        saveQuotations(unique);
        resolve(unique);
      } catch (err) {
        reject(err);
      }
    };
    reader.onerror = reject;
    reader.readAsText(file);
  });
}

// Auto-backup: keeps last N snapshots in localStorage
function createAutoBackup(quotations) {
  try {
    const backupData = localStorage.getItem(BACKUP_KEY);
    let backups = backupData ? JSON.parse(backupData) : [];
    
    backups.unshift({
      timestamp: new Date().toISOString(),
      count: quotations.length,
      data: quotations,
    });

    // Keep only last N backups
    if (backups.length > MAX_BACKUPS) {
      backups = backups.slice(0, MAX_BACKUPS);
    }

    localStorage.setItem(BACKUP_KEY, JSON.stringify(backups));
  } catch (e) {
    // If localStorage is full, remove oldest backups
    try {
      localStorage.removeItem(BACKUP_KEY);
    } catch {
      // Silently fail
    }
  }
}

// Restore from backup
export function getBackups() {
  try {
    const data = localStorage.getItem(BACKUP_KEY);
    if (!data) return [];
    const backups = JSON.parse(data);
    return backups.map(b => ({
      timestamp: b.timestamp,
      count: b.count,
    }));
  } catch {
    return [];
  }
}

export function restoreFromBackup(index) {
  try {
    const data = localStorage.getItem(BACKUP_KEY);
    if (!data) return null;
    const backups = JSON.parse(data);
    if (index >= 0 && index < backups.length) {
      saveQuotations(backups[index].data);
      return backups[index].data;
    }
    return null;
  } catch {
    return null;
  }
}
