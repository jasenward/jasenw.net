/*
----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
----How would you clean up this case Statement
----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
*/
	CASE	WHEN 
				(CASE	WHEN Phase LIKE 'D%' THEN 'O' 
						ELSE CASE	WHEN PayType = 'P1' THEN 'O' 
									ELSE CASE	WHEN PayType = 'EU' THEN 'E' 
												ELSE 'L' 
											END 
								END 
					END
				) = 'O' AND PayType <> 'P1' THEN LEFT(Department, 5) + 'C' 
			ELSE CASE	WHEN Job LIKE '%A' OR Job LIKE '%B' THEN Job 
						ELSE Department 
					END 
		END AS Department,

/*
@Jasen ANSWER #1: 
Collapse the CASE conditional logic significantly. There are CASE statements here that do nothing meaningful and conditions which conflict with one another.
The outcome is a determination of a result for PayType=’P1’ because the result set of having both have PayType = ‘P1’ and <> ‘P1’ will always be NULL. The outcome is AS Department.
Therefore the only thing that matters is the result of either LEFT(Department, 5)+C, Job, or Department. The conditionals being matched here are doing way too much work. 

Simplify as follows (This was tested in MySQL and results in the same output except that implicit concatenation is not allowed and so the + operator was replaced during the test with CONCAT() function):
*/
	CASE WHEN (Phase LIKE 'D%' AND PayType<>'P1') THEN LEFT(Department, 5)+'C'
	ELSE
		CASE WHEN (Job LIKE '%A' OR Job LIKE '%B') then Job
		ELSE Department 
       END
	END
        AS Department

----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
----What might be the unintended Consequence of this where clause
----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	SELECT	Time_PerspectiveKey
			,VersionsKey
			,MonthKey
			,AccountsKey
			,OrganizationKey
			,CASE	WHEN AccountsKey IN ('46998', '46999') THEN - 1 * [VALUE] ELSE Value END AS Value_Adjusted
	FROM	dbo.FactTableFinancial_Cube
	WHERE	AccountsKey >= '40000' 
			AND VersionsKey = 'ACT' 
			AND AccountsKey <= '41999' 
			AND NOT (OrganizationKey LIKE 'B%') 
			AND NOT (OrganizationKey LIKE 'C%')
			AND NOT (OrganizationKey LIKE 'G%') 
			OR
			AccountsKey IN ('46998', '46999') 
			AND NOT (OrganizationKey LIKE 'B%') 
			AND NOT (OrganizationKey LIKE 'C%') 
			AND NOT (OrganizationKey LIKE 'G%')
/*
@Jasen ANSWER #2: 
The same record will appear multiple times in the result set because a single record could match both condition subparts. For example, a record with AccountsKey ‘46998’ AND OrganizationKey ‘Z’ will be listed twice.
/*


----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
----How would you re-write these case statements
----XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
*/
	,CASE	WHEN (CASE WHEN Transaction_Type = 'I' AND - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
																		ELSE Retention_Amount END)) > 0 THEN - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
																														ELSE Retention_Amount 
																													END)) 
						ELSE CASE WHEN Transaction_Type = 'C'  AND - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
																				ELSE Retention_Amount END)) < 0 THEN - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
																																	ELSE Retention_Amount 
																						   										END)) 
									ELSE 0 
								END 
					END) <> 0 THEN 0 
				ELSE (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Invoice_Extension) ELSE Invoice_Extension END) + - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
																																ELSE Retention_Amount 
																															END)) 
			END AS Invoice_Amount

	,CASE	WHEN Transaction_Type = 'I' AND - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) 
															ELSE Retention_Amount 
														END)) > 0 THEN - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) ELSE Retention_Amount END)) 
				ELSE CASE WHEN Transaction_Type = 'C' AND - (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) ELSE Retention_Amount END)) < 0 THEN
								- (1 * (CASE WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount) ELSE Retention_Amount END)) 
						ELSE 0 
					END 
			END AS Retention,

/*
@Jasen ANSWER #3:
This is a multipart answer and since you did ask for my comments I will preface this section with the following. It should be noted that the only
 real complication to this SQL is that it is an exercise in logic deconstruction. I have been presented with queries like this in real world scenarios and found that 
it is cheaper, faster and customers are happier with the result when the query is thrown away and the developer revisits the design with the customer to better
 understand the intention of the tool using this query. However, for the purposes of a precursor interview and to demonstrate that I am capable of refactoring SQL,
 I proceeded for a limited the amount of time to refactor the query as requested. I limited my time to approximately 2 hours, which I believe to 
 be a fair investment of my personal time to demonstrate that I am capable of understanding queries and refactoring them. I will admit, that given such
 constraints it is entirely possible that there may be errors as a result, however, I believe that what I have shown below demonstrates a good understanding 
 of SQL and a process that I might go through to refactor such queries in a realistic scenario.
 
*/ 
--================================================================================================
-- Rewritten Stage 0: Initial pass to order logic
--================================================================================================
,CASE
	WHEN (
			CASE
				WHEN Transaction_Type = 'I' 
					AND - (1 * (
					-- @Jasen Transaction_Type will never both = I and C, so the else is all that matters. Execute Non-commented.
					-- CASE 
					--				WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount)
					-- 				ELSE 
					Retention_Amount 
					-- 			END
								)) > 0
					THEN - (1 * (
					-- @Jasen Transaction_Type will never match C because the precondition was that it matched I execute non-commented
					-- CASE
					-- 		WHEN 
					--						Transaction_Type = 'C' 
					--							THEN - 1 * (Retention_Amount)
					--						ELSE 
					Retention_Amount 
					--				END
					))
				ELSE
					CASE
						WHEN
							Transaction_Type = 'C'
							-- @Jasen the precondition is always met, therefore Always return the conditional, execute non-commented
							 AND - (1 * (
							--				CASE
							--					WHEN 
							--						Transaction_Type = 'C'
							--							THEN 
														- 1 * (Retention_Amount)
							--						ELSE Retention_Amount 
							--				END
							--			)
									) < 0 
							THEN - (1 * (
							-- Only the matched conditional is available, no point in case selecting it. Return the THEN
							--				CASE
							--					WHEN 
							--						Transaction_Type = 'C' 
							--							THEN 
														- 1 * (Retention_Amount)
							--						ELSE Retention_Amount 
											END
										)
									)
						ELSE 0 
					END 
		END
		) <> 0 
			-- @Jasen Records matching the above conditionals will only come from a subset of Transaction_Type=C or I where the FX of calculation on Retention_Amount=0, otherwise it equals 0 anyway. Pointless.
			THEN 0
	    ELSE(
		-- @Jasen in the event that the result is no zero from the above add the negative or positive of Invoice_Extension and concatenate it to the negative of the negative or positive of Rentention_Amount respectively
		-- Transaction_Type=I result is Invoice_Extension + - Retention_Amount
		-- Transaction_Type=C result is -1*Invoice_Extension + - -1*Retention_Amount
		-- As Invoice Amount
				CASE
					WHEN Transaction_Type = 'C'
						THEN - 1 * (Invoice_Extension)
					ELSE Invoice_Extension 
				END
			) + - (1 * (
							CASE
								WHEN Transaction_Type = 'C'
									THEN - 1 * (Retention_Amount)
								ELSE Retention_Amount 
							END
						)
					) 
END
	AS Invoice_Amount,
CASE
	WHEN 
		Transaction_Type = 'I' AND - (1 * (
		-- @Jasen, again, Transaction_Type can not both = I and C, so Fall through to Retention_Amount
		--		CASE
		--			WHEN Transaction_Type = 'C' 
		--				THEN - 1 * (Retention_Amount)
		--			ELSE 
					Retention_Amount 
		--		END
				)
			) > 0
			-- @Jasen, Transaction_Type will only equal I, therefore any conditional involving any other value is moot.
			--	THEN 
				- (1 * (
			--			CASE
			--				WHEN Transaction_Type = 'C' THEN - 1 * (Retention_Amount)
			--				ELSE 
							Retention_Amount 
			--			END
						)
					)
	ELSE 
		CASE
			WHEN Transaction_Type = 'C' AND - (1 * (
			-- @Jasen, Transaction_Type will only ever equal C, no condition is needed
			--		CASE
			--			WHEN Transaction_Type = 'C' 
			--				THEN - 1 * (Retention_Amount)
			--			ELSE
						Retention_Amount 
			--		END
				)
			) < 0 
				THEN 
					- (1 * (
			--		CASE
			--			WHEN Transaction_Type = 'C'
			--				THEN 
							- 1 * (Retention_Amount)
			--			ELSE Retention_Amount 
			--		END
				)
				)
		ELSE 0 	
	END 
END AS Retention, 

--================================================================================================
-- Rewritten Stage 1: Removing Redundant, Illogical CASE Selects
--================================================================================================ 
,CASE
	WHEN (
			CASE
				WHEN Transaction_Type = 'I' 
					AND - (1 * (Retention_Amount )) > 0
					THEN - (1 * ( Retention_Amount ))
				ELSE
					CASE
						WHEN
							Transaction_Type = 'C'
							 AND - (1 * ( - 1 * (Retention_Amount)) < 0 
							THEN - (1 * ( - 1 * (Retention_Amount)))
						ELSE 0 
					END 
		END
		) <> 0 
			THEN 0
	    ELSE(
				CASE
					WHEN Transaction_Type = 'C'
						THEN - 1 * (Invoice_Extension)
					ELSE Invoice_Extension 
				END
			) + - (1 * (
							CASE
								WHEN Transaction_Type = 'C'
									THEN - 1 * (Retention_Amount)
								ELSE Retention_Amount 
							END
						)
					) 
END
	AS Invoice_Amount,
CASE
	WHEN 
		Transaction_Type = 'I' 
		AND - (1 * (	Retention_Amount )) > 0
			THEN 
				- (1 * (Retention_Amount))
	ELSE 
		CASE
			WHEN Transaction_Type = 'C' AND - (1 * (Retention_Amount)) < 0 
				THEN 
					- (1 * (- 1 * (Retention_Amount)))
		ELSE 0 	
	END 
END AS Retention, 

--================================================================================================
-- Rewritten Stage 2: Removing Mathematical Redundancy
--================================================================================================ 
,CASE
	WHEN (
	-- @Jasen The results here are still illogical, Returning only values that are non-zero for an
	-- expression that returns 0 for any value that isn't zero.
	--		CASE
	--			WHEN Transaction_Type = 'I' 
	--				AND - (1 * (Retention_Amount )) > 0
	--				THEN - (1 * ( Retention_Amount ))
	--			ELSE
	--				CASE
	--					WHEN
	--						Transaction_Type = 'C'
	--						AND Retention_Amount < 0 
	--						THEN Retention_Amount
	--					ELSE 0 
	--				END 
	--	END
	--	) <> 0 
	--		THEN 
			0
	    ELSE(
		-- @Jasen this can be simplified
		CASE WHEN Transaction_Type= 'C'
				-- THEN - 1 * (Invoice_Extension) + -1 * (- 1 * (Retention_Amount)
				-- Further Symplified
				THEN Retention_Amount - Invoice_Extension
				ELSE Invoice_Extension - Retention_Amount
--					CASE
--						WHEN Transaction_Type = 'C'
--							THEN - 1 * (Invoice_Extension)
--						ELSE Invoice_Extension 
--					END
--				) + - (1 * (
--								CASE
--									WHEN Transaction_Type = 'C'
--										THEN - 1 * (Retention_Amount)
--									ELSE Retention_Amount 
--								END
--							)
--						) 
END
	AS Invoice_Amount,
CASE
	WHEN 
		Transaction_Type = 'I' 
		AND - (1 * (	Retention_Amount )) > 0
			THEN 
				- (1 * (Retention_Amount))
	ELSE 
		CASE
			WHEN Transaction_Type = 'C' AND - (1 * (Retention_Amount)) < 0 
				THEN 
					- (1 * (- 1 * (Retention_Amount)))
		ELSE 0 	
	END 
END AS Retention, 

--================================================================================================
-- Rewritten Stage 3: Clean up and finalization
--================================================================================================ 
,CASE
	WHEN (
		CASE
			WHEN (Transaction_Type = 'I' OR Transaction_Type = 'C') And Retention_Amount<0
				THEN 0
	    ELSE(
			CASE 
				WHEN Transaction_Type= 'C'
					THEN Retention_Amount - Invoice_Extension
				ELSE Invoice_Extension - Retention_Amount 
			END)
		END)
	AS Invoice_Amount,
CASE
	WHEN 
		Transaction_Type = 'I' 
		AND - (1 * (	Retention_Amount )) > 0
			THEN 
				- (1 * (Retention_Amount))
	ELSE 
		CASE
			WHEN Transaction_Type = 'C' AND - (1 * (Retention_Amount)) < 0 
				THEN 
					- (1 * (- 1 * (Retention_Amount)))
		ELSE 0 	
	END 
END AS Retention, 