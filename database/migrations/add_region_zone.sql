ALTER TABLE users 
ADD COLUMN region VARCHAR(50) NOT NULL DEFAULT 'Region 1',
ADD COLUMN zone VARCHAR(100) NOT NULL DEFAULT 'SA Zone 1';
