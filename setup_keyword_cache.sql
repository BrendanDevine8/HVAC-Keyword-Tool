-- Database caching table for Google Suggest results
CREATE TABLE IF NOT EXISTS keyword_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_hash VARCHAR(32) NOT NULL UNIQUE,
    query_text VARCHAR(255) NOT NULL,
    ip_address VARCHAR(15) NOT NULL,
    suggestions JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_query_hash (query_hash),
    INDEX idx_expires (expires_at)
);

-- Cleanup expired cache entries
DELETE FROM keyword_cache WHERE expires_at < NOW();