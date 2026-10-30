# Premium lane mutation ledger

|Owner|Record|Original|Change|Restoration|
|---|---|---|---|---|
|Premium|URL access rule 4 display name|Manual Screenshot Conflict URL Rule|Add RQA0208 suffix; eligibility, outcome and priority unchanged|Saved and reloaded; original restored and reload verified. First potentially concurrent attempt did not persist; isolated repeat passed.|
|Premium|Role Mapping rule 1 name|Pro Plan grants Pro Member role|Add RQA0208 suffix|Saved and reloaded; original restored and reload verified.|
|Premium|Discount rule 1 name|Gold members save 15%|Add RQA0208 suffix|Saved and reloaded; original restored and reload verified.|
|Premium → coordinator handoff|Disabled Shop Access rule 1 name|Private member store|CLI input appended: Private member storePrivate member store RQA0208|Coordinator restored the exact original name, saved and reloaded; checkbox remains false. Proof: evidence/shop-rule-restored.txt. Eligibility and outcome unchanged; CPT queue stopped.|
|Premium|Cancellation wizard local draft|Retention and all 4 offers disabled; discount 20%|Unsaved retention and offer switches enabled; discount 101% validation then restored to 20%|No Save. Original renderer froze; recovery session verified saved retention and all offers remain disabled.|
|Premium + commerce|Customer 3252 USD credit balance|0.00; no history|Grant 1.00, ledger 109499; apply 1.00 to checkout order 109510, debit ledger 109515|Admin reload confirms 0.00 and exactly two +1.00/−1.00 history rows. No further adjustment needed.|
