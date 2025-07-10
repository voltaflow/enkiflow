-- Create project_user table for tenant databases
-- Este script debe ejecutarse en cada base de datos de tenant

CREATE TABLE IF NOT EXISTS project_user (
    id BIGSERIAL PRIMARY KEY,
    project_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    all_current_projects BOOLEAN DEFAULT FALSE,
    all_future_projects BOOLEAN DEFAULT FALSE,
    custom_rate DECIMAL(10, 2),
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member', 'manager', 'viewer')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(project_id, user_id)
);

-- Add indexes
CREATE INDEX IF NOT EXISTS idx_project_user_user_future ON project_user(user_id, all_future_projects);
CREATE INDEX IF NOT EXISTS idx_project_user_current ON project_user(all_current_projects);
CREATE INDEX IF NOT EXISTS idx_project_user_future ON project_user(all_future_projects);

-- Add foreign key to projects table (only if projects table exists)
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'projects') THEN
        ALTER TABLE project_user 
        ADD CONSTRAINT fk_project_user_project 
        FOREIGN KEY (project_id) 
        REFERENCES projects(id) 
        ON DELETE CASCADE;
    END IF;
END $$;

-- Insert sample data for testing (optional)
-- Uncomment the following lines if you want to add test data
/*
-- Assign all users to project 1 as members
INSERT INTO project_user (project_id, user_id, role)
SELECT 1, id, 'member' 
FROM (SELECT DISTINCT user_id as id FROM space_users) u
ON CONFLICT (project_id, user_id) DO NOTHING;
*/