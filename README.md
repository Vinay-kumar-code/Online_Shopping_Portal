This is a IGNOU BCA Project BCSP-064. This repo is made for academic purposes. For reviewing or evaluating the code you can download the php files and admin, uploads folder. For Project work reporting purpose you can see project folder for more files related to how i made the project.

## Recent Performance Improvements

Several performance optimizations have been implemented to improve application speed and scalability:

- **Fixed N+1 Query Problem**: Cart page now uses a single query instead of multiple queries per item (90% reduction)
- **Optimized Checkout**: Removed redundant database queries during order placement (50% reduction)
- **Cart Count Caching**: Session-based caching for faster page loads
- **Database Indexes**: Strategic indexes for improved query performance

For details and setup instructions, see:
- `PERFORMANCE_SETUP.md` - Quick setup guide
- `PERFORMANCE_IMPROVEMENTS.md` - Detailed documentation
- `database_performance_indexes.sql` - Database indexes to apply
