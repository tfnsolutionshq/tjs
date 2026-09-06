<?php

namespace App\Support;

final class FormHelp
{
    /**
     * @var array<string, string>
     */
    private static array $texts = [
        // Article form
        'article.journal_id' => 'The journal this article belongs to. Determines public URL prefix and catalog scope.',
        'article.issue_id' => 'Optional issue assignment. Links the article to a volume/issue on the journal archive.',
        'article.volume_number' => 'Numeric volume identifier (e.g. 5 for Volume 5).',
        'article.volume_year' => 'Publication year for the volume record.',
        'article.volume_title' => 'Optional display title for the volume.',
        'article.volume_status' => 'Draft volumes are hidden from the public archive until published.',
        'article.issue_volume' => 'Which volume this new issue belongs to.',
        'article.issue_number' => 'Issue number within the selected volume (e.g. 2).',
        'article.issue_title' => 'Optional label shown alongside the issue number.',
        'article.issue_status' => 'Draft issues stay internal until you publish them.',
        'article.title' => 'Full article title as it should appear on the public site and in citations.',
        'article.slug' => 'URL-friendly identifier. Leave blank to auto-generate from the title.',
        'article.author_user_id' => 'Optional link to a member account. Used for ownership and access rules when set.',
        'article.abstract' => 'Summary shown on article pages and used in search and discovery.',
        'article.categories' => 'Topic labels from the platform taxonomy. Articles can have multiple categories.',
        'article.keywords' => 'Comma-separated terms that help readers find this work.',
        'article.authors' => 'People credited on the article. Add only the details you have; skip unknown optional fields.',
        'article.doi' => 'Digital Object Identifier. Leave blank if not yet registered with a DOI agency.',
        'article.license' => 'Rights under which readers may reuse this work. Shown on the public article page.',
        'article.page_range' => 'Print-style page span (e.g. 12–28) for citation metadata.',
        'article.mins_read' => 'Estimated reading time in whole minutes, shown to readers.',
        'article.galley' => 'Full-text file (PDF/DOC/DOCX) served to authorized readers.',
        'article.visibility' => 'Who can access the full text: everyone, members, paid buyers, or nobody (closed).',
        'article.price_amount' => 'Price in the smallest currency unit (e.g. kobo for NGN). Required when visibility is Paid.',
        'article.currency' => 'ISO currency code for paid articles (e.g. NGN, USD).',
        'article.status' => 'Draft articles are not public. Published articles appear in the catalog when visibility allows.',

        // Author metadata keys (article author rows)
        'author.surname' => 'Family name used in citations (displayed as Surname, First name).',
        'author.given_names' => 'First name(s) as they should appear after the surname.',
        'author.middle_name' => 'Optional middle name or initial.',
        'author.email' => 'Contact email for this author. Not always shown publicly.',
        'author.affiliation' => 'Institution or organization the author represents.',
        'author.nationality' => 'Country associated with the author, stored as a standard country name.',
        'author.orcid' => 'ORCID iD (0000-0000-0000-0000) for persistent researcher identification.',
        'author.role' => 'How this person contributed (author, co-author, editor, etc.).',

        // Journal form
        'journal.title' => 'Official journal name shown on the public site and in admin lists.',
        'journal.initials' => 'Short label used when no logo is uploaded. Does not need to be unique.',
        'journal.slug' => 'Permanent URL segment (/j/slug). Cannot be changed after creation.',
        'journal.subtitle' => 'Optional tagline shown under the journal title.',
        'journal.description' => 'About text for the journal landing and about pages.',
        'journal.issn' => 'Print ISSN if the journal has one (format 0000-0000).',
        'journal.eissn' => 'Electronic ISSN for online editions.',
        'journal.publisher' => 'Publishing body credited on articles and metadata.',
        'journal.default_license' => 'Default license applied to new articles unless overridden per article.',
        'journal.language' => 'Primary language of the journal for metadata and UI defaults.',
        'journal.review_type' => 'Default peer-review policy for new submissions: closed (blind) or open (reviewers see author identity).',
        'journal.logo' => 'Square or landscape logo on the journal home page and listings. PNG/JPG/WebP, max 10MB.',
        'journal.header_image' => 'Banner behind the journal title. Prefer images without baked-in text. Max 15MB.',
        'journal.header_overlay' => 'Darkens the banner so white title text stays readable (0 = none, 0.9 = strongest).',
        'journal.accent_color' => 'Brand color for buttons, links, and accents on the public journal site.',
        'journal.theme' => 'Visual layout preset for the public journal pages.',
        'journal.status' => 'Inactive journals are hidden from public listings and submission pickers.',
        'journal.featured' => 'Featured journals are highlighted on the platform homepage. Only platform administrators can enable this.',
        'journal.platform_admin_edits' => 'When on, platform administrators can edit this journal from the platform admin portal.',
        'journal.show_subtitle_in_header' => 'Shows the subtitle under the journal title on the public site header.',
        'journal.assign_admin_now' => 'Create or assign a journal admin during setup. You can also do this later.',
        'journal.accepts_submissions' => 'When off, authors cannot start new submissions to this journal.',
        'journal.visibility' => 'Whether the journal appears in the public catalog.',
        'journal.team_mode' => 'Add an existing platform user or create a new account for the team member.',
        'journal.team_email' => 'Login email for the new team member account.',
        'journal.team_name' => 'Display name for the new team member.',
        'journal.team_role' => 'Journal admin has full manage access; editor handles editorial workflow; reviewer reviews assigned papers; production editor prepares accepted manuscripts for publication.',
        'journal.team_user' => 'Existing TJS user to add to this journal’s team.',

        // Editorial board
        'board.name' => 'Full name as shown on the public editorial board page.',
        'board.role' => 'Board title (e.g. Editor-in-Chief, Associate Editor).',
        'board.affiliation' => 'Institution listed beside the member’s name.',
        'board.email' => 'Optional contact email. May be shown publicly depending on journal settings.',
        'board.orcid' => 'Optional ORCID link for the board member.',
        'board.sort_order' => 'Lower numbers appear first on the editorial board listing.',

        // Platform settings
        'settings.name' => 'Short platform name used in UI chrome and compact labels.',
        'settings.full_name' => 'Full legal or marketing name used in citations and footers.',
        'settings.organization' => 'Organization credited as the platform operator.',
        'settings.publisher' => 'Default publisher name when a journal does not set its own.',
        'settings.default_license' => 'Fallback license for new journals and articles.',
        'settings.default_language' => 'ISO 639-1 language code (e.g. en) for platform defaults.',
        'settings.currency' => 'Default currency code for memberships and paid content.',
        'settings.membership_platform_price' => 'Price charged for platform-wide membership when that product is active.',
        'settings.membership_platform_days' => 'How many days a platform membership purchase lasts.',
        'settings.journal_activation_price' => 'Price charged to list a journal and unlock management when activation fees are active.',
        'settings.journal_activation_days' => 'How long a paid journal activation lasts before renewal is required.',
        'settings.category_name' => 'Label shown on article category chips and filters.',
        'settings.category_active' => 'Inactive categories are hidden from article forms but kept for existing records.',

        // Volumes & issues
        'volume.number' => 'Volume number within the journal (often aligned with year).',
        'volume.year' => 'Year associated with this volume.',
        'volume.title' => 'Optional descriptive title for the volume.',
        'volume.status' => 'Published volumes and their issues appear on the public archive.',

        // Membership plans
        'plan.journal' => 'Leave as platform-wide or scope this plan to one journal.',
        'plan.scope' => 'Platform plans grant access across all journals; journal plans are limited to one title.',
        'plan.name' => 'Name shown to members at checkout and in their account.',
        'plan.description' => 'Short explanation of what the plan includes.',
        'plan.price' => 'Price in whole currency units (not kobo/cents unless your locale expects that).',
        'plan.currency' => 'Currency charged for this plan.',
        'plan.duration_days' => 'Access period after purchase before renewal is needed.',
        'plan.active' => 'Inactive plans cannot be purchased but existing memberships remain valid.',

        // Submissions (admin / manage)
        'submission.reviewer' => 'Reviewer who will assess this manuscript. They receive an assignment notification.',
        'submission.priority' => 'Editorial priority from 1 (lowest) to 5 (highest) for this assignment.',
        'submission.due_at' => 'Optional deadline shown to the reviewer for completing the review.',
        'submission.publish_issue' => 'Issue where the approved submission will be published as an article.',
        'submission.publish_title' => 'Title for the published article. Defaults to the submission title.',
        'submission.publish_slug' => 'URL slug for the new article. Auto-generated if left blank.',
        'submission.publish_visibility' => 'Access level for the published article.',
        'submission.publish_license' => 'License on the published article record.',
        'submission.publish_abstract' => 'Abstract copied to the article page.',
        'submission.publish_keywords' => 'Keywords stored on the published article.',
        'submission.publish_doi' => 'Digital Object Identifier for the published article record.',
        'submission.publish_author_name' => 'Fallback author name when structured author rows are not provided.',
        'submission.publish_page_range' => 'Print-style page span (e.g. 12–28) for the published article.',
        'submission.publish_authors_text' => 'Optional free-text author list. One per line: Name|email|affiliation|orcid.',

        // Author submission
        'submission.journal_id' => 'Journal associated with the selected call for submissions.',
        'submission.review_type' => 'Peer-review policy applied to this manuscript (closed hides author identity from reviewers).',
        'submission.title' => 'Full manuscript title as it should appear in the editorial workflow.',
        'submission.abstract' => 'Brief summary editors and reviewers use to assess the work.',
        'submission.category' => 'Subject area that best matches your manuscript.',
        'submission.keywords' => 'Comma-separated terms describing your topic.',
        'submission.document' => 'Main manuscript file (DOC or DOCX only, max 50MB). PDFs are not accepted because editors need an editable file.',
        'submission.announcement_id' => 'Open call for submissions and target issue you are submitting to.',
        'submission.revision_file' => 'Revised manuscript file (DOC or DOCX only). PDFs are not accepted.',
        'submission.revision_notes' => 'Explain what changed in this revision for the editors.',

        // Reviewer
        'review.decision' => 'Your recommendation: accept, reject, or request revisions before acceptance.',
        'review.comment' => 'Feedback for editors (and sometimes authors). Be constructive and specific.',
        'review.rejection_reason' => 'Required summary when rejecting. Editors may share this with the author.',

        // Announcements
        'announcement.type' => 'News posts updates only. Calls for submissions open a specific issue to manuscript intake.',
        'announcement.title' => 'Headline shown on the journal website and author submission picker.',
        'announcement.summary' => 'Short teaser for announcement listings.',
        'announcement.body' => 'Full announcement text shown on the public detail page.',
        'announcement.guidelines' => 'Guidelines for authors — shown on the public call page and submission form. Include formatting rules, file requirements, and any submission or publication fees.',
        'announcement.issue' => 'Issue authors must submit to while this call is open.',
        'announcement.opens_at' => 'When authors can start submitting to this call.',
        'announcement.closes_at' => 'After this time the call closes and no new submissions are accepted.',

        // User admin
        'user.name' => 'Display name across the platform.',
        'user.email' => 'Login email and primary contact address.',
        'user.role' => 'Platform role controls admin access and default capabilities.',
        'user.position' => 'Job title shown on profile and reviewer pages when applicable.',
        'user.affiliation' => 'Institution or organization on the user profile.',
        'user.orcid' => 'Researcher ORCID iD for identification on public reviewer listings.',
        'user.bio' => 'Short professional biography for profile and public reviewer pages.',
        'user.avatar' => 'Optional profile photo (JPG, PNG, WEBP, or GIF, max 5MB). Shown on your profile and public reviewer listings.',
        'user.public_reviewer' => 'When on, your name may appear on journal reviewer pages if you review for them.',
        'user.password' => 'Must meet the platform minimum length and security rules.',
        'user.password_confirmation' => 'Re-enter the password to confirm there are no typos.',
        'user.current_password' => 'Your existing password, required to confirm sensitive account changes.',

        // Auth
        'auth.name' => 'Your full name as it will appear in submissions and correspondence.',
        'auth.email' => 'Used to sign in and receive system notifications.',
        'auth.password' => 'Choose a strong password you do not use on other sites.',
        'auth.remember' => 'Stay signed in on this device until you log out.',
    ];

    public static function get(string $key): ?string
    {
        return self::$texts[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset(self::$texts[$key]);
    }
}
